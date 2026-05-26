# eSC Chess Club Manager

Aplicatie PHP pentru administrarea unui club de sah: antrenori, sali, activitati, membri, competitii, clasamente, premii, deconturi, exporturi si notificari server-side.

Proiectul este construit strict pe backend PHP, fara JavaScript.

## Cerinte

- XAMPP cu Apache, MySQL/MariaDB si PHP
- Extensii PHP uzuale pentru PDO MySQL si DOM
- Browser modern

## Instalare

1. Copiaza proiectul in directorul XAMPP:

   ```text
   C:\xampp\htdocs\eSC-Chess-Club-Manager-
   ```

2. Porneste Apache si MySQL din XAMPP Control Panel.

3. Creeaza baza de date in phpMyAdmin:

   ```sql
   CREATE DATABASE esc_chess_club CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Importa `database.sql` in baza de date `esc_chess_club`, sau lasa aplicatia sa creeze/completeze schema prin `includes/schema.php` la prima accesare.

5. Verifica setarile din `includes/config.php`:

   ```php
   $host = 'localhost';
   $dbname = 'esc_chess_club';
   $user = 'root';
   $pass = '';
   ```

6. Deschide aplicatia:

   ```text
   http://localhost/eSC-Chess-Club-Manager-/login.php
   ```

7. Conturi demo:

   ```text
   admin / admin123
   antrenor / antrenor123
   membru / membru123
   ```

## Roluri si acces

Aplicatia foloseste roluri in tabela `users`.

- `admin`: acces complet, exact ca fluxul initial al aplicatiei. Poate adauga, modifica, sterge, importa CSV, exporta JSON/XML si gestiona deconturi.
- `coach`: acces de vizualizare la datele clubului; poate gestiona programul activitatilor si rezultatele competitiilor: participanti, punctaje si premii acordate.
- `member`: acces de vizualizare. Poate vedea antrenori, sali, activitati, competitii, clasamente, profilul propriu si istoricul propriu.

Zonele financiare (`travel_expenses.php`, `travel_report.php`, `export_decont.php`) si exporturile globale (`export_data_json.php`, `export_data_xml.php`) sunt disponibile doar pentru admin.

Dashboard-ul si meniul principal sunt adaptate dupa rol:

- Admin: `Acasa`, `Membri`, `Antrenori`, `Sali`, `Activitati`, `Competitii`, `Istoric`, `Deconturi`, `Raport`.
- Antrenor: `Acasa`, `Program`, `Jucatori`, `Competitii`, `Istoric`.
- Membru: `Acasa`, `Profil`, `Program`, `Competitii`, `Istoricul meu`.

Scopul este ca fiecare rol sa ajunga direct la zonele utile, fara tab-uri care duc la pagini doar informative sau fara actiuni relevante pentru acel rol.

## Structura bazei de date

Tabele principale:

- `users`: conturi pentru autentificare, roluri si legaturi optionale catre `members` sau `coaches`.
- `coaches`: antrenori si colaboratori.
- `rooms`: sali disponibile pentru activitati.
- `activities`: programari pe sali, cu data si interval orar.
- `members`: membri ai clubului, cu tip, nivel si antrenor asociat.
- `competitions`: competitii si turnee.
- `competition_participants`: legatura unica dintre membri si competitii, cu punctaj.
- `awards`: premii disponibile.
- `member_awards`: premii acordate membrilor.
- `travel_expenses`: deconturi pentru deplasari.

Relatii importante:

- `members.id_antrenor_asociat` refera `coaches.id`.
- `activities.id_sala` refera `rooms.id`.
- `competition_participants.id_competitie` refera `competitions.id`.
- `competition_participants.id_membru` refera `members.id`.
- `member_awards.id_award` refera `awards.id`.
- `member_awards.id_membru` refera `members.id`.
- `member_awards.id_competitie` refera optional `competitions.id`.
- `users.id_membru` poate lega un cont cu rol `member` de un membru.
- `users.id_antrenor` poate lega un cont cu rol `coach` de un antrenor.

## Importuri CSV

Fisierele de test sunt in `data_import/`.

Ordine recomandata:

```text
antrenori_test.csv
sali_test.csv
membri_test.csv
competitii_test.csv
activitati_test.csv
premii_test.csv
participanti_test.csv
premii_membri_test.csv
deconturi_test.csv
```

Importurile sunt idempotente: daca acelasi CSV este incarcat de doua ori, randurile existente sunt omise si nu sunt duplicate.

## Microserviciu API

Microserviciul este:

```text
api/stats.php
```

Endpoint:

```text
http://localhost/eSC-Chess-Club-Manager-/api/stats.php
```

Raspunde cu JSON si header:

```php
header('Content-Type: application/json; charset=utf-8');
```

Date returnate:

- `generated_at`: momentul generarii raspunsului.
- `members_count`: numarul de membri.
- `competition_today`: `true` daca exista competitie in ziua curenta.
- `today_competitions`: lista competitiilor de azi.

## Plugin notificari PHP

Pluginul este:

```text
plugin_notificari.php
```

Este inclus in layout-ul principal din:

```text
includes/header.php
```

Pluginul functioneaza ca un client HTTP server-side:

```php
$pluginResponse = @file_get_contents($pluginStatsUrl, false, $pluginContext);
```

El consuma microserviciul `api/stats.php`, decodeaza JSON-ul si, daca `competition_today` este `true`, randeaza direct un toast HTML in coltul ecranului. Nu foloseste JavaScript.

## Exporturi

Export JSON:

```text
export_data_json.php
```

Export XML:

```text
export_data_xml.php
```

Exportul XML foloseste clasa nativa PHP:

```php
DOMDocument
```

## Responsivitate si accesibilitate

Meniul mobil foloseste CSS Checkbox Hack:

- `input type="checkbox" id="menu-toggle"` este ascuns vizual, dar ramane accesibil pentru tastatura.
- `label for="menu-toggle"` controleaza meniul hamburger.
- CSS-ul afiseaza navigatia pe mobil cu:

  ```css
  #menu-toggle:checked ~ nav {
      display: flex;
  }
  ```

Tabelele sunt infasurate in `.table-responsive`, care permite scroll orizontal pe ecrane mici.

Masuri a11y:

- inputurile vizibile au `label for` legat de `id`.
- navigatia are `aria-label`.
- pagina are skip link catre continut.
- focusul pe meniul hamburger este vizibil.
- culorile principale au contrast bun pe fundalurile folosite.
