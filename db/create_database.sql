-- Dota Knocker database

-- Matches
CREATE TABLE match(
    id INTEGER PRIMARY KEY NOT NULL
);

-- Player IDs
CREATE TABLE player(
    id INTEGER PRIMARY KEY NOT NULL
);

-- Hero use records
CREATE TABLE heroUse(
    id INTEGER PRIMARY KEY NOT NULL,
    heroId INTEGER NOT NULL,
    useCount INTEGER NOT NULL,
    playerId INTEGER NOT NULL,
    FOREIGN KEY(playerId) references player(id),
    UNIQUE(heroId, playerId)
);

-- Personas
CREATE TABLE persona(
    name TEXT PRIMARY KEY NOT NULL,
    playerId INTEGER NOT NULL,
    FOREIGN KEY(playerId) REFERENCES player(id)
);
