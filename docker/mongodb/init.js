db = db.getSiblingDB('sidegigs');

db.createUser({
    user: 'sidegigs',
    pwd: 'sidegigs_secret',
    roles: [{ role: 'readWrite', db: 'sidegigs' }]
});

db.createCollection('users');
db.createCollection('clients');
db.createCollection('workflows');
db.createCollection('tasks');
db.createCollection('task_events');
