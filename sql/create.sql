CREATE TABLE log
(
    id bigserial PRIMARY KEY,
    system text NULL,
    log json NOT NULL
);