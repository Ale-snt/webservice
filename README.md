==========================
 PizzaOnline – Da Mario
 Progetto scolastico PHP
==========================

COME AVVIARE IL PROGETTO
--------------------------
1. Avvia XAMPP (Apache + MySQL)
2. Apri phpMyAdmin: http://localhost/phpmyadmin
3. Crea un nuovo database chiamato "pizza_online"
   OPPURE importa direttamente il file database.sql
   (tasto "Importa" in phpMyAdmin)
4. Copia l'intera cartella "pizza_online" dentro:
     C:\xampp\htdocs\pizza_online
5. Apri il browser su: http://localhost/pizza_online

CREDENZIALI DI DEFAULT
--------------------------
Admin : admin@damario.it  / admin123
Staff : staff@damario.it  / admin123

Per creare un cliente: clicca "Registrati" sulla home.

STRUTTURA FILE
--------------------------
config.php    – connessione al database + sessione
style.css     – foglio di stile
index.php     – homepage con menu
register.php  – registrazione cliente
login.php     – accesso
logout.php    – uscita
order.php     – ordine cliente + storico ordini
staff.php     – pannello staff (gestione ordini)
admin.php     – pannello admin (menu + utenti)
database.sql  – script SQL da importare

FUNZIONALITA PER RUOLO
--------------------------
Cliente : registrarsi, accedere, ordinare, vedere
          i propri ordini, annullare se "in attesa"
Staff   : vedere tutti gli ordini, aggiornare stato
          (in attesa > in lavorazione > pronto > consegnato)
Admin   : tutto dello staff + gestire prodotti del menu
          + promuovere/retrocedere utenti
