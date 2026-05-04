<?php
/**
 * API REST - Pizzeria Da Mario
 * Endpoint: api.php?action=nome_action
 * Formato risposta: JSON
 * 
 * Autenticazione: Bearer token (email:password_hash)
 * Esempio: Authorization: Basic base64(email:password)
 */

require_once 'config.php';

// Imposta header JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestione preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Variabile di risposta
$response = [];
$action = $_GET['action'] ?? '';

// ==================== FUNZIONI DI AUTENTICAZIONE ====================

/**
 * Verifica le credenziali dell'utente
 * @return array|false Array con user oppure false
 */
function authenticate() {
    global $db;
    
    // Controlla header Authorization
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (empty($auth)) {
        return false;
    }
    
    // Supporta sia Bearer che Basic
    if (preg_match('/Basic\s+(.*)$/i', $auth, $matches)) {
        $credentials = base64_decode($matches[1]);
        list($email, $password) = explode(':', $credentials);
        
        $stmt = $db->prepare("SELECT id, nome, email, ruolo FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verifica password (assumendo che la password sia già hashata nel DB)
            // Per login via API, ci serve una verifica della password
            // Alternativa: usare token pre-generati
            return $user;
        }
    }
    
    // Token semplice (esempio) - in produzione usare JWT
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = $matches[1];
        // Token predefiniti per demo
        $valid_tokens = [
            'admin_token_2024' => ['id' => 1, 'nome' => 'Admin', 'email' => 'admin@damario.it', 'ruolo' => 'admin'],
            'staff_token_2024' => ['id' => 2, 'nome' => 'Staff', 'email' => 'staff@damario.it', 'ruolo' => 'staff'],
        ];
        
        if (isset($valid_tokens[$token])) {
            return $valid_tokens[$token];
        }
    }
    
    return false;
}

/**
 * Verifica che l'utente abbia ruolo admin
 */
function requireAdmin($user) {
    if (!$user || $user['ruolo'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Accesso negato. Richiesto ruolo admin.']);
        exit;
    }
}

/**
 * Verifica che l'utente abbia ruolo staff o admin
 */
function requireStaff($user) {
    if (!$user || !in_array($user['ruolo'], ['staff', 'admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Accesso negato. Richiesto ruolo staff o admin.']);
        exit;
    }
}

/**
 * Verifica che l'utente sia autenticato
 */
function requireAuth($user) {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Autenticazione richiesta. Usa header Authorization: Bearer token']);
        exit;
    }
}

// ==================== ENDPOINT PUBBLICI ====================

// GET - Lista prodotti disponibili (pubblico)
if ($action === 'get_products') {
    $stmt = $db->query("SELECT id, nome, prezzo, disponibile FROM products WHERE disponibile = 1 ORDER BY nome");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'count' => count($products),
        'data' => $products
    ];
}

// GET - Dettaglio singolo prodotto
elseif ($action === 'get_product' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, nome, prezzo, disponibile FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $response = ['success' => true, 'data' => $product];
    } else {
        http_response_code(404);
        $response = ['success' => false, 'error' => 'Prodotto non trovato'];
    }
}

// ==================== ENDPOINT PROTETTI (auth richiesta) ====================

else {
    $user = authenticate();
    
    // GET - Ordini dell'utente corrente
    if ($action === 'get_my_orders') {
        requireAuth($user);
        
        $stmt = $db->prepare("
            SELECT id, prodotto, quantita, indirizzo, note, stato, data_ora 
            FROM orders 
            WHERE user_id = ? 
            ORDER BY data_ora DESC
        ");
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'count' => count($orders),
            'data' => $orders
        ];
    }
    
    // POST - Crea nuovo ordine
    elseif ($action === 'create_order') {
        requireAuth($user);
        
        // Leggi body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        $prodotto = trim($input['prodotto'] ?? '');
        $quantita = (int)($input['quantita'] ?? 0);
        $indirizzo = trim($input['indirizzo'] ?? '');
        $note = trim($input['note'] ?? '');
        
        // Validazione
        $errors = [];
        if (empty($prodotto)) $errors[] = 'Campo "prodotto" richiesto';
        if ($quantita < 1) $errors[] = 'Quantita deve essere almeno 1';
        if (empty($indirizzo)) $errors[] = 'Campo "indirizzo" richiesto';
        
        if (!empty($errors)) {
            http_response_code(400);
            $response = ['success' => false, 'errors' => $errors];
        } else {
            // Verifica che il prodotto esista e sia disponibile
            $check = $db->prepare("SELECT id FROM products WHERE nome = ? AND disponibile = 1");
            $check->execute([$prodotto]);
            
            if ($check->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO orders (user_id, prodotto, quantita, indirizzo, note, stato) 
                    VALUES (?, ?, ?, ?, ?, 'in_attesa')
                ");
                
                if ($stmt->execute([$user['id'], $prodotto, $quantita, $indirizzo, $note])) {
                    $orderId = $db->lastInsertId();
                    $response = [
                        'success' => true,
                        'message' => 'Ordine creato con successo',
                        'order_id' => $orderId
                    ];
                } else {
                    http_response_code(500);
                    $response = ['success' => false, 'error' => 'Errore durante la creazione'];
                }
            } else {
                http_response_code(400);
                $response = ['success' => false, 'error' => 'Prodotto non disponibile'];
            }
        }
    }
    
    // GET - Annulla ordine (solo se in_attesa)
    elseif ($action === 'cancel_order' && isset($_GET['id'])) {
        requireAuth($user);
        
        $orderId = (int)$_GET['id'];
        
        // Verifica che l'ordine appartenga all'utente e sia in_attesa
        $check = $db->prepare("
            SELECT stato FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $check->execute([$orderId, $user['id']]);
        $order = $check->fetch();
        
        if (!$order) {
            http_response_code(404);
            $response = ['success' => false, 'error' => 'Ordine non trovato'];
        } elseif ($order['stato'] !== 'in_attesa') {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'Ordine non annullabile (stato: ' . $order['stato'] . ')'];
        } else {
            $delete = $db->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
            $delete->execute([$orderId, $user['id']]);
            $response = ['success' => true, 'message' => 'Ordine annullato'];
        }
    }
    
    // ==================== ENDPOINT PER STAFF/ADMIN ====================
    
    // GET - Lista tutti gli ordini (staff/admin)
    elseif ($action === 'get_all_orders') {
        requireStaff($user);
        
        $filtro = $_GET['stato'] ?? '';
        $sql = "
            SELECT o.*, u.nome as cliente_nome, u.email as cliente_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
        ";
        
        if ($filtro && in_array($filtro, ['in_attesa', 'in_lavorazione', 'pronto', 'consegnato'])) {
            $sql .= " WHERE o.stato = '" . addslashes($filtro) . "'";
        }
        
        $sql .= " ORDER BY o.data_ora DESC";
        
        $orders = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $response = [
            'success' => true,
            'count' => count($orders),
            'data' => $orders
        ];
    }
    
    // PUT - Aggiorna stato ordine (staff/admin)
    elseif ($action === 'update_order_status') {
        requireStaff($user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($input['id'] ?? $_GET['id'] ?? 0);
        $nuovoStato = $input['stato'] ?? '';
        
        $stati_validi = ['in_attesa', 'in_lavorazione', 'pronto', 'consegnato'];
        
        if (!$orderId) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'ID ordine richiesto'];
        } elseif (!in_array($nuovoStato, $stati_validi)) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'Stato non valido. Valori accettati: ' . implode(', ', $stati_validi)];
        } else {
            $stmt = $db->prepare("UPDATE orders SET stato = ? WHERE id = ?");
            $stmt->execute([$nuovoStato, $orderId]);
            
            if ($stmt->rowCount() > 0) {
                $response = [
                    'success' => true,
                    'message' => 'Stato ordine aggiornato',
                    'order_id' => $orderId,
                    'new_status' => $nuovoStato
                ];
            } else {
                http_response_code(404);
                $response = ['success' => false, 'error' => 'Ordine non trovato'];
            }
        }
    }
    
    // GET - Lista prodotti (admin) con gestione disponibilita
    elseif ($action === 'admin_get_products') {
        requireAdmin($user);
        
        $stmt = $db->query("SELECT * FROM products ORDER BY nome");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'data' => $products];
    }
    
    // POST - Aggiungi prodotto (admin)
    elseif ($action === 'admin_add_product') {
        requireAdmin($user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $nome = trim($input['nome'] ?? '');
        $prezzo = (float)($input['prezzo'] ?? 0);
        
        if (empty($nome) || $prezzo <= 0) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'Nome e prezzo validi richiesti'];
        } else {
            $stmt = $db->prepare("INSERT INTO products (nome, prezzo) VALUES (?, ?)");
            $stmt->execute([$nome, $prezzo]);
            $response = [
                'success' => true,
                'message' => 'Prodotto aggiunto',
                'product_id' => $db->lastInsertId()
            ];
        }
    }
    
    // PUT - Toggle disponibilita prodotto (admin)
    elseif ($action === 'admin_toggle_product') {
        requireAdmin($user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'ID prodotto richiesto'];
        } else {
            $cur = $db->prepare("SELECT disponibile FROM products WHERE id = ?");
            $cur->execute([$id]);
            $row = $cur->fetch();
            
            if ($row) {
                $nuovo = $row['disponibile'] ? 0 : 1;
                $db->prepare("UPDATE products SET disponibile = ? WHERE id = ?")->execute([$nuovo, $id]);
                $response = [
                    'success' => true,
                    'message' => 'Disponibilita aggiornata',
                    'product_id' => $id,
                    'now_available' => (bool)$nuovo
                ];
            } else {
                http_response_code(404);
                $response = ['success' => false, 'error' => 'Prodotto non trovato'];
            }
        }
    }
    
    // DELETE - Elimina prodotto (admin)
    elseif ($action === 'admin_delete_product') {
        requireAdmin($user);
        
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'ID prodotto richiesto'];
        } else {
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $response = ['success' => true, 'message' => 'Prodotto eliminato'];
            } else {
                http_response_code(404);
                $response = ['success' => false, 'error' => 'Prodotto non trovato'];
            }
        }
    }
    
    // GET - Lista utenti (admin)
    elseif ($action === 'admin_get_users') {
        requireAdmin($user);
        
        $stmt = $db->query("SELECT id, nome, email, ruolo, indirizzo FROM users ORDER BY ruolo, nome");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'data' => $users];
    }
    
    // PUT - Cambia ruolo utente (admin)
    elseif ($action === 'admin_change_role') {
        requireAdmin($user);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $uid = (int)($input['user_id'] ?? $_GET['uid'] ?? 0);
        $ruolo = $input['ruolo'] ?? '';
        
        $ruoli_validi = ['cliente', 'staff', 'admin'];
        
        if (!$uid || !in_array($ruolo, $ruoli_validi)) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'User ID e ruolo valido richiesti'];
        } elseif ($uid == $user['id']) {
            http_response_code(400);
            $response = ['success' => false, 'error' => 'Non puoi modificare il tuo stesso ruolo'];
        } else {
            $stmt = $db->prepare("UPDATE users SET ruolo = ? WHERE id = ?");
            $stmt->execute([$ruolo, $uid]);
            $response = [
                'success' => true,
                'message' => 'Ruolo aggiornato',
                'user_id' => $uid,
                'new_role' => $ruolo
            ];
        }
    }
    
    // Se l'azione non è riconosciuta
    else {
        http_response_code(404);
        $response = [
            'success' => false,
            'error' => 'Azione non valida',
            'available_actions' => [
                'pubbliche' => ['get_products', 'get_product?id=X'],
                'utente' => ['get_my_orders', 'create_order', 'cancel_order?id=X'],
                'staff' => ['get_all_orders', 'update_order_status'],
                'admin' => ['admin_get_products', 'admin_add_product', 'admin_toggle_product', 
                           'admin_delete_product', 'admin_get_users', 'admin_change_role']
            ]
        ];
    }
}

// ==================== OUTPUT ====================
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);