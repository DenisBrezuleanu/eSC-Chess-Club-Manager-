<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

role_guard(is_admin(), 'Doar administratorul poate exporta datele aplicatiei.');

function xml_safe_name(string $name): string
{
    $safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);

    if ($safe === '' || preg_match('/^[0-9\-]/', $safe)) {
        $safe = 'field_' . $safe;
    }

    return $safe;
}

function append_rows(DOMDocument $dom, DOMElement $parent, string $rowName, array $rows): void
{
    foreach ($rows as $row) {
        $rowNode = $dom->createElement($rowName);

        foreach ($row as $key => $value) {
            $node = $dom->createElement(xml_safe_name((string)$key));
            $node->appendChild($dom->createTextNode((string)$value));
            $rowNode->appendChild($node);
        }

        $parent->appendChild($rowNode);
    }
}

$exports = [
    'members' => $pdo->query("SELECT * FROM members ORDER BY id ASC")->fetchAll(),
    'competitions' => $pdo->query("SELECT * FROM competitions ORDER BY data DESC, id DESC")->fetchAll(),
    'competition_participants' => $pdo->query("SELECT * FROM competition_participants ORDER BY id_competitie ASC, punctaj_obtinut DESC")->fetchAll(),
    'awards' => $pdo->query("SELECT * FROM awards ORDER BY id ASC")->fetchAll(),
    'member_awards' => $pdo->query("SELECT * FROM member_awards ORDER BY data_acordare DESC, id DESC")->fetchAll(),
    'travel_expenses' => $pdo->query("SELECT * FROM travel_expenses ORDER BY data DESC, id DESC")->fetchAll(),
];

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$root = $dom->createElement('esc_export');
$root->setAttribute('generated_at', date(DATE_ATOM));
$dom->appendChild($root);

foreach ($exports as $sectionName => $rows) {
    $section = $dom->createElement($sectionName);
    append_rows($dom, $section, 'item', $rows);
    $root->appendChild($section);
}

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="esc_export.xml"');

echo $dom->saveXML();
exit();
