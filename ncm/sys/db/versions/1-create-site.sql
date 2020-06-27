-- TODO Daten generieren
--
-- File generated with SQLiteStudio v3.2.1 on Sa. Juni 27 01:59:57 2020
--
-- Text encoding used: UTF-8
--
PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table: abbreviations
CREATE TABLE abbreviations (
                               abbreviation VARCHAR (100) NOT NULL,
                               lang         VARCHAR (25)  NOT NULL,
                               description  TEXT          NOT NULL
                                   DEFAULT ('')
);


-- Table: article
CREATE TABLE article (
                         id                     INTEGER       PRIMARY KEY AUTOINCREMENT
                             UNIQUE
                                                              NOT NULL,
                         creation_timestamp     DATETIME      NOT NULL
                                                              DEFAULT (datetime('now', 'localtime') ),
                         modification_timestamp DATETIME      NOT NULL
                                                              DEFAULT (datetime('now', 'localtime') ),
                         author_id              INTEGER       REFERENCES user (id)
                                                              NOT NULL,
                         medium_id              INTEGER       REFERENCES medium (id)
                                                              DEFAULT NULL,
                         status_code            INTEGER       NOT NULL
                                                              DEFAULT (0),
                         headline               TEXT          NOT NULL
                                                              DEFAULT (''),
                         teaser                 TEXT          NOT NULL
                                                              DEFAULT (''),
                         content                TEXT          NOT NULL
                                                              DEFAULT (''),
                         start_timestamp        DATETIME      NOT NULL
                                                              DEFAULT (datetime('now', 'localtime') ),
                         stop_timestamp         DATETIME,
                         publishing_timestamp   DATETIME      DEFAULT (datetime('now', 'localtime') ),
                         enable_trackbacks      INTEGER       NOT NULL
                                                              DEFAULT (1),
                         enable_comments        INTEGER       NOT NULL
                                                              DEFAULT (1),
                         articletype_key        VARCHAR (100) NOT NULL
                                                              DEFAULT ('default'),
                         templatevars           TEXT          NOT NULL
                                                              DEFAULT (''),
                         series_id              INTEGER
);


-- Table: articleseries
CREATE TABLE articleseries (
                               id                     INTEGER       PRIMARY KEY AUTOINCREMENT
                                   UNIQUE
                                                                    NOT NULL,
                               creation_timestamp     DATETIME      NOT NULL
                                                                    DEFAULT (datetime('now', 'localtime') ),
                               modification_timestamp DATETIME      NOT NULL
                                                                    DEFAULT (datetime('now', 'localtime') ),
                               status_code            INTEGER       NOT NULL
                                                                    DEFAULT (0),
                               title                  TEXT          NOT NULL
                                                                    DEFAULT (''),
                               description            TEXT          DEFAULT ('')
                                   NOT NULL,
                               sorting_key            VARCHAR (100) NOT NULL
                                                                    DEFAULT ('default')
);


-- Table: comment
CREATE TABLE comment (
                         id                     INTEGER  PRIMARY KEY AUTOINCREMENT
                             UNIQUE
                                                         NOT NULL,
                         article_id             INTEGER  NOT NULL
                             REFERENCES article (id),
                         creation_timestamp     DATETIME NOT NULL
                             DEFAULT (datetime('now', 'localtime') ),
                         modification_timestamp DATETIME NOT NULL
                             DEFAULT (datetime('now', 'localtime') ),
                         status_code            INTEGER  NOT NULL
                             DEFAULT (0),
                         spam_status            INTEGER  NOT NULL
                             DEFAULT (0),
                         username               TEXT     NOT NULL
                             DEFAULT (''),
                         email                  TEXT     NOT NULL
                             DEFAULT (''),
                         headline               TEXT     NOT NULL
                             DEFAULT (''),
                         content                TEXT     NOT NULL
                             DEFAULT (''),
                         use_gravatar           INTEGER  NOT NULL
                             DEFAULT (0)
);


-- Table: definition
CREATE TABLE definition (
                            definitiontype VARCHAR (100) NOT NULL,
                            [key]          VARCHAR (100) NOT NULL,
                            title          TEXT          NOT NULL
                                DEFAULT (''),
                            value          TEXT          NOT NULL
                                DEFAULT (''),
                            parameters     TEXT          NOT NULL
                                DEFAULT (''),
                            PRIMARY KEY (
                                         definitiontype COLLATE NOCASE ASC,
                                         [key] COLLATE NOCASE ASC
                                )
                                ON CONFLICT REPLACE
)
    WITHOUT ROWID;


-- Table: imageformat
CREATE TABLE imageformat (
                             [key]       VARCHAR (50)  PRIMARY KEY
                                                       NOT NULL,
                             title       VARCHAR (100) NOT NULL
                                 DEFAULT (''),
                             description TEXT          NOT NULL
                                 DEFAULT (''),
                             width       INTEGER       NOT NULL
                                 DEFAULT (0),
                             height      INTEGER       NOT NULL
                                 DEFAULT (0)
);


-- Table: medium
CREATE TABLE medium (
                        id                     INTEGER       PRIMARY KEY AUTOINCREMENT
                            UNIQUE
                                                             NOT NULL,
                        entrytype              INTEGER (1)   NOT NULL
                            DEFAULT (0),
                        parent_id              INTEGER       NOT NULL
                            DEFAULT (0),
                        creation_timestamp     DATETIME      NOT NULL
                            DEFAULT (datetime('now', 'localtime') ),
                        modification_timestamp DATETIME      NOT NULL
                            DEFAULT (datetime('now', 'localtime') ),
                        status_code            INTEGER       NOT NULL
                            DEFAULT (0),
                        filename               VARCHAR (100) NOT NULL
                            DEFAULT (''),
                        filesize               INTEGER       NOT NULL
                            DEFAULT (0),
                        extension              VARCHAR (10)  NOT NULL
                            DEFAULT (''),
                        type                   VARCHAR (50)  NOT NULL
                            DEFAULT ('binary/octet-stream'),
                        title                  VARCHAR (100) NOT NULL
                            DEFAULT (''),
                        description            TEXT          NOT NULL
                            DEFAULT (''),
                        attribution            TEXT          NOT NULL
                            DEFAULT (''),
                        hash                   VARCHAR (32)  NOT NULL
                            DEFAULT ('')
);


-- Table: page
CREATE TABLE page (
                      id                     INTEGER   PRIMARY KEY AUTOINCREMENT
                          UNIQUE
                                                       NOT NULL,
                      creation_timestamp     DATETIME  NOT NULL
                                                       DEFAULT (datetime('now', 'localtime') ),
                      modification_timestamp DATETIME  NOT NULL
                                                       DEFAULT (datetime('now', 'localtime') ),
                      author_id              INTEGER   REFERENCES user (id)
                                                       NOT NULL,
                      status_code            INTEGER   NOT NULL
                                                       DEFAULT (0),
                      url                    TEXT (50) UNIQUE
                                                       NOT NULL,
                      headline               TEXT      NOT NULL,
                      content                TEXT      NOT NULL,
                      publishing_timestamp   DATETIME  DEFAULT (datetime('now', 'localtime') )
);


-- Table: setting
CREATE TABLE setting (
                         name    TEXT PRIMARY KEY
                             NOT NULL
                             UNIQUE,
                         setting TEXT DEFAULT ''
                             NOT NULL,
                         params  TEXT DEFAULT ''
                             NOT NULL
);


-- Table: tag_article
CREATE TABLE tag_article (
                             tag        VARCHAR (50) NOT NULL
                                 COLLATE NOCASE,
                             article_id INTEGER      REFERENCES article (id)
                                                     NOT NULL,
                             PRIMARY KEY (
                                          tag COLLATE NOCASE ASC,
                                          article_id
                                 )
                                 ON CONFLICT REPLACE
)
    WITHOUT ROWID;


-- Table: tag_medium
CREATE TABLE tag_medium (
                            tag       VARCHAR (50) NOT NULL
                                COLLATE NOCASE,
                            medium_id INTEGER      NOT NULL
                                REFERENCES medium (id),
                            PRIMARY KEY (
                                         tag COLLATE NOCASE ASC,
                                         medium_id
                                )
                                ON CONFLICT REPLACE
)
    WITHOUT ROWID;


-- Table: user
CREATE TABLE user (
                      id                     INTEGER  PRIMARY KEY AUTOINCREMENT
                                                      NOT NULL,
                      status_code            INTEGER  DEFAULT 0
                          NOT NULL,
                      creation_timestamp     DATETIME DEFAULT (datetime('now', 'localtime') )
                          NOT NULL,
                      modification_timestamp DATETIME DEFAULT (datetime('now', 'localtime') )
                          NOT NULL,
                      firstname              TEXT     DEFAULT ''
                          NOT NULL,
                      lastname               TEXT     DEFAULT ''
                          NOT NULL,
                      username               TEXT     NOT NULL,
                      password               TEXT     NOT NULL,
                      last_login_timestamp   TEXT,
                      email                  TEXT     NOT NULL
                                                      DEFAULT (''),
                      usertype               INTEGER  DEFAULT 0
                          NOT NULL
);


-- Table: userlist
CREATE TABLE userlist (
                          id                     INTEGER      PRIMARY KEY AUTOINCREMENT
                              UNIQUE
                                                              NOT NULL,
                          [key]                  VARCHAR (50) UNIQUE
                                                              NOT NULL
                              DEFAULT (''),
                          title                  TEXT         NOT NULL,
                          status_code            INTEGER      NOT NULL
                              DEFAULT (0),
                          creation_timestamp     DATETIME     NOT NULL
                              DEFAULT (datetime('now', 'localtime') ),
                          modification_timestamp DATETIME     NOT NULL
                              DEFAULT (datetime('now', 'localtime') )
);


-- Table: userlistitem
CREATE TABLE userlistitem (
                              id                     INTEGER  PRIMARY KEY AUTOINCREMENT
                                  UNIQUE
                                                              NOT NULL,
                              userlist_id            INTEGER  REFERENCES userlist (id)
                                                              NOT NULL,
                              parent_id              INTEGER  REFERENCES userlistitem (id),
                              status_code            INTEGER  NOT NULL
                                  DEFAULT (0),
                              creation_timestamp     DATETIME NOT NULL
                                  DEFAULT (datetime('now', 'localtime') ),
                              modification_timestamp DATETIME NOT NULL
                                  DEFAULT (datetime('now', 'localtime') ),
                              title                  TEXT     NOT NULL,
                              content                TEXT     NOT NULL,
                              parameters             TEXT     NOT NULL
                                  DEFAULT (''),
                              sorting_code           INTEGER  NOT NULL
                                  DEFAULT (0)
);


-- Index: setting_name_uindex
CREATE UNIQUE INDEX setting_name_uindex ON setting (
                                                    name
    );


-- Index: user_id_uindex
CREATE UNIQUE INDEX user_id_uindex ON user (
                                            id
    );


COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
