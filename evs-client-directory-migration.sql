-- Re-enter client police, address, and equipment data from the EVS reference sheet.
-- Run once against the evs_drills database. Existing drills and history are preserved.
USE evs_drills;

-- Existing clients from the seeded directory.
UPDATE clients SET address = '1901 Ocean Parkway, Brooklyn, New York', state = 'NY', has_speaker = 0, has_siren = 0, police_department = 'Shomrim', police_hotline = '(718)-338-9797' WHERE name = 'SCC (Sephardic Community Center)';
UPDATE clients SET address = '4th Street, Brooklyn, New York', state = 'NY', has_speaker = 1, has_siren = 1, police_department = 'Shomrim', police_hotline = '(718)-338-9797' WHERE name = 'Michael Betesh';
UPDATE clients SET address = '1100 East 7th Street, Brooklyn, New York', state = 'NY', has_speaker = 1, has_siren = 1, police_department = 'Shomrim', police_hotline = '(718)-338-9797' WHERE name = 'Joe Hamway';
UPDATE clients SET address = '724 Avenue K, Brooklyn, New York', state = 'NY', has_speaker = 1, has_siren = 0, police_department = 'Shomrim', police_hotline = '(718)-338-9797' WHERE name = 'Joy Mahana';
UPDATE clients SET address = '2093 East 1st Street, corner of Avenue U, Brooklyn, New York', state = 'NY', has_speaker = 1, has_siren = 0, police_department = 'Shomrim', police_hotline = '(718)-338-9797' WHERE name = 'Jack Ezon';

UPDATE clients SET address = '125 Ocean Avenue, Deal, New Jersey', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Deal Police Department', police_hotline = '(732)-531-1113' WHERE name = 'DSN Beach Club';
UPDATE clients SET address = '115 Almyr Avenue, Deal, New Jersey', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Deal Police Department', police_hotline = '(732)-531-1113' WHERE name = 'Mandy Cohen';
UPDATE clients SET address = '11 Hathaway Avenue, Deal, New Jersey', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Deal Police Department', police_hotline = '(732)-531-1113' WHERE name = 'Eli Cohen';

UPDATE clients SET address = '124 East 65th Street, Manhattan, New York', state = 'NY', has_speaker = 1, has_siren = 1, police_department = '911', police_hotline = '911' WHERE name = 'Shaul Nakash';
UPDATE clients SET address = '210 S Willaman Drive, Beverly Hills, Los Angeles', state = 'CA', has_speaker = 1, has_siren = 1, police_department = '911', police_hotline = '911' WHERE name = 'Liron';
UPDATE clients SET address = '33 Jim Lynch Drive, Long Branch, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 1, police_department = '911', police_hotline = '911' WHERE name = 'Steve Levy';
UPDATE clients SET address = '1887 Richmond Ave #5, Staten Island, New York 10314', state = 'NY', has_speaker = 1, has_siren = 0, police_department = 'NYPD 121st Precinct', police_hotline = '(718) 697-8700' WHERE name = 'The Clinic';

UPDATE clients SET address = '4 Clark Court, Oakhurst, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 1, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'Raymond Saka';
UPDATE clients SET address = '224 Park Avenue, Oakhurst, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 1, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'Jack Braha';
UPDATE clients SET address = '44 Shadow Lawn Drive, Oakhurst, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 1, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'Charles Saka';
UPDATE clients SET address = '45 Shadow Lawn Drive, Oakhurst, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 1, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'Margie Saka';
UPDATE clients SET address = '395 Deal Road, Ocean, New Jersey', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'CMD Hatzalah';
UPDATE clients SET address = '7 Ross Court, Oakhurst, New Jersey', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Ocean Police Department', police_hotline = '(732)-531-1800' WHERE name = 'Myron Rumeld';
UPDATE clients SET address = '261 Dixon Avenue, Long Branch, New Jersey 07740', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Long Branch Local Police', police_hotline = '(732)-222-1000' WHERE name = 'Morris Laniado';
UPDATE clients SET address = '45 Norwood Ave N, Allenhurst, New Jersey', state = 'NJ', has_speaker = 1, has_siren = 0, police_department = 'Allenhurst Local Police', police_hotline = '(732)-531-2255' WHERE name = 'Isaac Ash';

UPDATE clients SET name = 'Miami', address = '3200 AH We Wa Street, Miami, Florida', state = 'FL', has_speaker = 1, has_siren = 0, police_department = '911', police_hotline = '911' WHERE name = 'Julia Rivin';
UPDATE clients SET address = '655 Howard Avenue, Somerset, New Jersey 08873', state = 'NJ', has_speaker = 0, has_siren = 0, police_department = 'Somerset Local Police', police_hotline = '(732)-873-5533' WHERE name = 'Somerset Warehouse';

-- Additional clients from the reference sheet. These inserts are idempotent by name.
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Ahaba Shul', '1744 Ocean Parkway, Brooklyn, New York', 'NY', 0, 0, 'Shomrim', '(718)-338-9797'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Ahaba Shul');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Joey Esses', '91 Parker Avenue, Deal, New Jersey', 'NJ', 1, 1, 'Deal Police Department', '(732)-531-1113'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Joey Esses');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Jack Haddad', '55 Lawrence Avenue, Deal, New Jersey', 'NJ', 1, 1, 'Deal Police Department', '(732)-531-1113'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Jack Haddad');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Isaac Chehebar', '98 Jerome Avenue, Deal, New Jersey', 'NJ', 0, 0, 'Deal Police Department', '(732)-531-1113'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Isaac Chehebar');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT '321 Broadway', '321 Broadway, New York', 'NY', 1, 0, '911', '911'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = '321 Broadway');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Snack Innovation Drive-In', '41 Ethel Road, Piscataway, New Jersey', 'NJ', 0, 1, '911', '911'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Snack Innovation Drive-In');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Abe Cohen', '26 Saxony Drive, Oakhurst, New Jersey', 'NJ', 1, 1, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Abe Cohen');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Morris Cohen', '3 Saxony Drive, Oakhurst, New Jersey', 'NJ', 1, 1, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Morris Cohen');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Morris Sutton', '14 Harvard Court, Oakhurst, New Jersey', 'NJ', 1, 1, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Morris Sutton');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Mark Massry', '101 Larchwood Avenue, Oakhurst, New Jersey', 'NJ', 1, 1, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Mark Massry');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Social Center/Hatzalah', '395 Deal Road, Ocean, New Jersey', 'NJ', 1, 0, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Social Center/Hatzalah');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Eddie Betesh', '401 Parker Avenue, Ocean, New Jersey', 'NJ', 1, 0, 'Ocean Police Department', '(732)-531-1800'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Eddie Betesh');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Ohel Yishak', '108 Allen Avenue, Allenhurst, New Jersey', 'NJ', 0, 0, 'Allenhurst Local Police', '(732)-531-2255'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Ohel Yishak');
INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline)
SELECT 'Alex Goldin', '83 Oak Glen Road, Howell, New Jersey', 'NJ', 0, 0, 'Howell Township Police', '(732)-938-4111'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE name = 'Alex Goldin');
