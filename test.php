<?php

$outputPath = 'result_php.tsv';

function getFieldValues(array $fields, string $code): array {

    foreach ($fields as $field) {
        if ($field['field_code'] === $code) {
            return array_column($field['values'] ?? [], 'value');
        }
    }

    return [];
}

$leadsJson = json_decode(file_get_contents('leads.json'), true);
$contactsJson = json_decode(file_get_contents('contacts.json'), true);

$leadsArray = $leadsJson[0]['_embedded']['leads'] ?? [];
$contactsArray = $contactsJson[0]['_embedded']['contacts'] ?? [];

$contactById = [];
foreach ($contactsArray as $contact) {
    $contactById[$contact['id']] = $contact;
}

file_put_contents($outputPath, "lead_id\tcontacts_ids\tphones\temails\n");

foreach ($leadsArray as $lead) {

    $phones = [];
    $emails = [];
    $contactIds = array_map(fn($contact) => $contact['id'], $lead['_embedded']['contacts'] ?? []);

    foreach ($contactIds as $id) {
        if (!isset($contactById[$id]) || empty($contactById[$id]['custom_fields_values'])) {
            continue;
        } 

        $fields = $contactById[$id]['custom_fields_values'];

        $rawPhones = getFieldValues($fields, 'PHONE');
        $cleanedPhones = array_map(fn($phone) => preg_replace('/\D+/', '', $phone), $rawPhones);
        foreach ($cleanedPhones as $phone) {
            if ($phone && !in_array($phone, $phones)) {
                $phones[] = $phone;
            }
        }

        $rawEmails = getFieldValues($fields, 'EMAIL');
        foreach ($rawEmails as $email) {
            if ($email && !in_array($email, $emails)) {
                $emails[] = $email;
            }
        }

    }

    $string = $lead['id'] . "\t" . implode(',', $contactIds) . "\t" . implode(',', $phones) . "\t" . implode(',', $emails) . "\n";
    file_put_contents($outputPath, $string, FILE_APPEND);
}

echo "Success";
