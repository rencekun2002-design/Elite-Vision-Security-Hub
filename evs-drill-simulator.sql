-- EVS Command Center Hub — MySQL schema + seed data
-- Import this file in phpMyAdmin (http://localhost/phpmyadmin -> Import) with XAMPP's MySQL running.
-- It creates the database, all tables, and loads the existing clients/drills/team so the app works immediately.

CREATE DATABASE IF NOT EXISTS evs_drills CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evs_drills;

-- ---------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------

CREATE TABLE operators (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'Operator'
);

CREATE TABLE executives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'CEO'
);

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255) NOT NULL,
  state VARCHAR(10) DEFAULT 'NY',
  has_speaker TINYINT(1) NOT NULL DEFAULT 0,
  has_siren TINYINT(1) NOT NULL DEFAULT 0,
  police_department VARCHAR(150),
  police_hotline VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE incidents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category ENUM('green','orange','red') NOT NULL DEFAULT 'orange',
  client_id INT NOT NULL,
  time_label VARCHAR(50),
  narrative TEXT NOT NULL,
  ideal_tools JSON NOT NULL,
  extra_keywords JSON,
  is_complex TINYINT(1) NOT NULL DEFAULT 0,
  stage2_branch_good TEXT,
  stage2_branch_bad TEXT,
  stage2_bad_cat ENUM('green','orange','red') DEFAULT NULL,
  stage2_ideal_tools JSON,
  stage2_extra_keywords JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE drill_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  operator_name VARCHAR(120),
  client_name VARCHAR(150),
  category VARCHAR(10),
  tool_pct INT,
  script_pct INT,
  overall_pct INT,
  detail_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  doc_type ENUM('google_drive', 'uploaded', 'external_url') NOT NULL DEFAULT 'external_url',
  url VARCHAR(500),
  file_path VARCHAR(500),
  category VARCHAR(100),
  uploaded_by VARCHAR(120),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------
-- Seed: team
-- ---------------------------------------------------------------

INSERT INTO operators (name, role) VALUES
 ('Aicee Sindayen', 'Supervisor'),
 ('Jericho Anasco', 'Operator'),
 ('Ivan Vicente', 'Operator'),
 ('Rhannie Sam Velasco', 'Operator'),
 ('Clarence Salvador', 'Supervisor'),
 ('Manuel Panganiban', 'Operator'),
 ('Alrianne Dela Cruz', 'Operator'),
 ('Jessa Ferreras', 'Operator'),
 ('Justine Ivanne Bautista', 'Operator'),
 ('Jadon Godwin Unite', 'Operator'),
 ('Joselito Mario Rosete', 'Operator'),
 ('James Maldecir', 'Operator');

INSERT INTO executives (name, role) VALUES
 ('Eric', 'CEO'),
 ('Nadal', 'CEO');

-- ---------------------------------------------------------------
-- Seed: clients (explicit ids so the incident inserts below can reference them)
-- ---------------------------------------------------------------

INSERT INTO clients (id, name, address, state, has_speaker, has_siren) VALUES
 (1,  'Isaac Ash',                       '45 Norwood Ave N, Allenhurst, NJ 07711',              'NJ', 1, 1),
 (2,  'Margie Saka',                     '45 Shadow Lawn Drive, Oakhurst, NJ',                   'NJ', 1, 1),
 (3,  'Charles Saka',                    '44 Shadow Lawn Drive, Oakhurst, NJ 07755',             'NJ', 1, 1),
 (4,  'Raymond Saka',                    '4 Clark Ct, Oakhurst, NJ 07755',                       'NJ', 0, 0),
 (5,  'Joy Mahana',                      '724 Ave K, Brooklyn, NY',                              'NY', 1, 1),
 (6,  'Jack Ezon',                       '2093 East 1st St, corner of Ave U, Brooklyn, NY',      'NY', 1, 1),
 (7,  'Joe Hamway',                      '1100 East 7th Street, Brooklyn, NY 11223',             'NY', 1, 1),
 (8,  'Eli Cohen',                       '11 Hathaway Avenue, Deal, NJ 07723',                   'NJ', 0, 0),
 (9,  'Myron Rumeld',                    '7 Ross Ct, Oakhurst, NJ 07755',                        'NJ', 0, 0),
 (10, 'Jack Braha',                      '224 Park Avenue, Oakhurst, NJ',                        'NJ', 1, 0),
 (11, 'Michael Betesh',                  '1916 East 4th Street, Brooklyn, NY 11223',             'NY', 1, 1),
 (12, 'SCC (Sephardic Community Center)','1901 Ocean Parkway, Brooklyn, NY 11223',               'NY', 0, 0),
 (13, 'DSN Beach Club',                  '125 Ocean Ave, Deal, NJ 07723',                        'NJ', 0, 0),
 (14, 'Steve Levy',                      '33 Jim Lynch Drive, Long Branch, NJ',                  'NJ', 1, 1),
 (15, 'Shaul Nakash',                    '124 East 65th Street, Manhattan, NY',                  'NY', 1, 1),
 (16, 'Mandy Cohen',                     '115 Almyr Avenue, Deal, NJ',                            'NJ', 0, 0),
 (17, 'Liron',                           '210 S Willaman Dr, Beverly Hills, CA',                 'CA', 1, 0),
 (18, 'The Clinic',                      '1887 Richmond Ave #5, Staten Island, NY 10314',        'NY', 0, 0),
 (19, 'Somerset Warehouse',              '665 Howard Ave, Somerset, NJ',                         'NJ', 0, 0),
 (20, 'CMD Hatzalah',                    '395 Deal Rd, Ocean, NJ 07712',                          'NJ', 1, 0),
 (21, 'Morris Laniado',                  '261 Dixon Ave, Long Branch, NJ 07740',                 'NJ', 0, 0),
 (22, 'Julia Rivin',                     '3200 AH We Wa St, Miami, FL 33133',                    'FL', 0, 0);

-- ---------------------------------------------------------------
-- Seed: drills / incidents
-- ---------------------------------------------------------------

INSERT INTO incidents (title, category, client_id, time_label, narrative, ideal_tools, extra_keywords, is_complex) VALUES
('Unsupervised child on porch', 'green', 22, '7:03 PM',
 'You observe one of the children playing on the front porch without any visible adult supervision. There is no sign of danger, but it is worth flagging.',
 '["chat"]', '["safety"]', 0),

('Unfamiliar vehicle idling outside', 'orange', 17, '11:52 PM',
 'An unfamiliar vehicle parks directly in front of the residence for several minutes. The occupants stay inside and appear to be watching the house.',
 '["speaker","police","chat"]', '["vehicle"]', 0),

('Person checking windows and door handles', 'orange', 9, '2:40 AM',
 'An individual walks around the property, peers into several windows, checks the garage side door, then tries the backyard gate before leaving.',
 '["police","chat"]', '["window","gate"]', 0),

('Intoxicated person blocking entrance', 'orange', 12, '3:00 AM',
 'An apparently intoxicated person wanders the property, briefly tries the double door, then lies down directly in front of the entrance.',
 '["police","chat"]', '["intoxicated"]', 0),

('Homeless individual attempting to take a bicycle', 'orange', 1, '1:20 AM',
 'A man is seen pacing along the front gate several times before entering the property and attempting to take a bicycle parked near the gate.',
 '["speaker","police","supervisor","chat"]', '["bicycle"]', 0),

('Package taken from front porch', 'orange', 4, '2:40 AM',
 'One unknown individual approaches the front door, picks up a delivery package that had been left at the front entrance, and quickly walks away with it.',
 '["police","ceo","chat"]', '["package"]', 0),

('Attempted forced entry, no tools available', 'orange', 18, '2:20 AM',
 'An unidentified individual pulls on the locked front door, then tries to pry open a window with their hands. After failing, they walk to the rear and check a delivery door before leaving on foot.',
 '["police","ceo","supervisor","chat"]', '["forced entry"]', 0),

('Forced entry through the front door', 'red', 1, '11:52 PM',
 'Two masked individuals force open the front door despite repeated speaker warnings and enter the residence.',
 '["speaker","siren","police","ceo","supervisor","chat"]', '["forced entry","masked"]', 0),

('Armed men approaching the residence', 'red', 16, '2:43 AM',
 'Three masked men exit a vehicle carrying crowbars and weapons. One moves toward the side door while the others crouch toward the front.',
 '["police","ceo","supervisor","chat"]', '["armed","weapons"]', 0),

('Arson attempt at the side gate', 'red', 1, '9:40 PM',
 'Five masked individuals fail to force the side gate open, then light a gasoline-filled bottle and throw it over the fence before fleeing.',
 '["police","ceo","supervisor","chat"]', '["gasoline"]', 0),

('Child taken from parking lot', 'red', 18, '2:05 PM',
 'A child exits the building unsupervised and is forcibly pulled into an unmarked, tinted vehicle by two masked individuals.',
 '["police","ceo","supervisor","chat"]', '["child","vehicle"]', 0),

('Person motionless after a fall', 'red', 2, '9:00 PM',
 'A family member falls and remains completely motionless. No movement is observed after several seconds.',
 '["speaker","siren","ambulance","ceo","supervisor","chat"]', '["ambulance","emergency"]', 0),

('Active vehicle break-in', 'red', 9, '3:05 AM',
 'An individual pries open a vehicle door, reaches inside, and removes items from the front seat before fleeing.',
 '["police","ceo","chat"]', '["vehicle","theft"]', 0),

('Physical altercation on camera', 'red', 17, '10:40 PM',
 'Two individuals are seen physically fighting in view of the camera, with no signs of stopping.',
 '["speaker","police","ceo","chat"]', '["fight"]', 0),

('Vehicle vandalized with a bat', 'red', 7, '10:35 PM',
 'A man wearing a white tank top repeatedly strikes a parked vehicle with a baseball bat.',
 '["police","ceo","chat"]', '["bat","vandalism"]', 0);

-- Complex, multi-stage drills

INSERT INTO incidents
 (title, category, client_id, time_label, narrative, ideal_tools, extra_keywords, is_complex,
  stage2_branch_good, stage2_branch_bad, stage2_bad_cat, stage2_ideal_tools, stage2_extra_keywords)
VALUES
('Scooter accident, no response', 'red', 2, '9:00 PM',
 'David rode a kick scooter through the garage, lost control while turning, and collided with a family vehicle. He appears seriously injured and is not moving.',
 '["speaker","siren","ambulance","chat"]', '["ambulance","emergency"]', 1,
 'You used the speaker to check on him and called 911 within the first minute. A family member rushes out from inside the house, and paramedics arrive shortly after to take over.',
 'Two more minutes pass with no response from inside the house and no ambulance called yet. David is still motionless, and no one from the family has come outside.',
 'red', '["ambulance","siren","supervisor","ceo","chat"]', '["ambulance","emergency"]'),

('Uniformed delivery person climbs the fence', 'orange', 1, '4:00 AM',
 'One person wearing an Amazon uniform approaches the side gate carrying a package. After about a minute standing at the gate, he climbs over the fence into the yard instead of leaving the package at the door.',
 '["speaker","police","chat"]', '["package"]', 1,
 'After the speaker warning and the police call, the individual immediately drops the package, climbs back over the fence, and flees on foot before officers arrive.',
 'With no warning issued, the individual disappears further into the backyard and out of camera view for several minutes before reappearing near the back door.',
 'red', '["police","supervisor","ceo","chat"]', '["package"]'),

('Unattended bag left on the front stairs', 'orange', 1, '6:15 PM',
 'A man in a kippah and all-white outfit sits on the front stairs for a while, leaves a black bag behind, and walks away without it.',
 '["police","chat"]', '["bag","unattended"]', 1,
 'Police were notified promptly and checked the bag on arrival — it was confirmed empty and harmless. No further action is needed beyond logging the incident.',
 'About fifteen minutes later, a second individual approaches, picks up the bag without hesitating, and quickly leaves the property in the opposite direction.',
 'red', '["police","supervisor","ceo","chat"]', '["bag"]');
