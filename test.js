const fs = require('fs');

const outputPath = 'result_node.tsv';

function getFieldValues(fields, code) {
    const field = fields.find(f => f.field_code === code);
    return field?.values?.map(v => v.value) || [];
}

const leadsJson = JSON.parse(fs.readFileSync('leads.json', 'utf-8'));
const contactsJson = JSON.parse(fs.readFileSync('contacts.json', 'utf-8'));

const leadsArray = leadsJson[0]?._embedded?.leads || [];
const contactsArray = contactsJson[0]?._embedded?.contacts || [];

const contactById = {};
for (const contact of contactsArray) {
    contactById[contact.id] = contact;
}

fs.writeFileSync(outputPath, 'lead_id\tcontacts_ids\tphones\temails\n');

for (const lead of leadsArray) {
    const contactIds = (lead._embedded?.contacts || []).map(contact => contact.id);
    const phones = new Set();
    const emails = new Set();

    for (const id of contactIds) {
        const contact = contactById[id];
        if (!contactById[id] || !Array.isArray(contact.custom_fields_values) || contact.custom_fields_values.length === 0) {
            continue;
        }

        const fields = contact.custom_fields_values;

        const rawPhones = getFieldValues(fields, 'PHONE');
        const cleanedPhones = rawPhones.map(phone => phone.replace(/\D+/g, ''));
        for (const phone of cleanedPhones) {
            if (phone) phones.add(phone);
        }

        const rawEmails = getFieldValues(fields, 'EMAIL');
        for (const email of rawEmails) {
            if (email) emails.add(email);
        }
    }

    const row =
        lead.id + '\t' +
        contactIds.join(',') + '\t' +
        [...phones].join(',') + '\t' +
        [...emails].join(',') + '\n';

    fs.appendFileSync(outputPath, row);
}

console.log('Success');
