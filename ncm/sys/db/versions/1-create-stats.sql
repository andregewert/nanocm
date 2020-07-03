--
-- File generated with SQLiteStudio v3.2.1 on So. Juni 28 21:08:28 2020
--
-- Text encoding used: UTF-8
--
PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table: accesslog
CREATE TABLE accesslog (
                           accesstime     DATETIME     NOT NULL
                               DEFAULT (datetime('now', 'localtime') ),
                           sessionid      VARCHAR (50) NOT NULL
                               DEFAULT (''),
                           method         VARCHAR (20) NOT NULL
                               DEFAULT (''),
                           url            TEXT         NOT NULL
                               DEFAULT (''),
                           fullurl        TEXT         NOT NULL
                               DEFAULT (''),
                           useragent      TEXT         NOT NULL
                               DEFAULT (''),
                           osname         VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           osversion      VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           browsername    VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           browserversion VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           country        VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           countrycode    VARCHAR (10) NOT NULL
                               DEFAULT (''),
                           region         VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           regionname     VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           city           VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           zip            VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           timezone       VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           latitude       FLOAT        NOT NULL
                               DEFAULT (0),
                           longitude      FLOAT        NOT NULL
                               DEFAULT (0)
);


-- Table: monthlybrowser
CREATE TABLE monthlybrowser (
                                year           INTEGER      NOT NULL
                                    DEFAULT (0),
                                month          INTEGER      NOT NULL
                                    DEFAULT (0),
                                browsername    VARCHAR (25) NOT NULL
                                    DEFAULT (''),
                                browserversion VARCHAR (25) NOT NULL
                                    DEFAULT (''),
                                count          INTEGER      NOT NULL
                                    DEFAULT (0),
                                PRIMARY KEY (
                                             year,
                                             month,
                                             browsername,
                                             browserversion
                                    )
                                    ON CONFLICT REPLACE
);


-- Table: monthlyos
CREATE TABLE monthlyos (
                           year      INTEGER      NOT NULL
                               DEFAULT (0),
                           month     INTEGER      NOT NULL
                               DEFAULT (0),
                           osname    VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           osversion VARCHAR (25) NOT NULL
                               DEFAULT (''),
                           count     INTEGER      NOT NULL
                               DEFAULT (0),
                           PRIMARY KEY (
                                        year,
                                        month,
                                        osname,
                                        osversion
                               )
                               ON CONFLICT REPLACE
);


-- Table: monthlyregion
CREATE TABLE monthlyregion (
                               year        INTEGER      NOT NULL
                                   DEFAULT (0),
                               month       INTEGER      NOT NULL
                                   DEFAULT (0),
                               country     VARCHAR (25) NOT NULL
                                   DEFAULT (''),
                               countrycode VARCHAR (10) NOT NULL
                                   DEFAULT (''),
                               regionname  VARCHAR (25) NOT NULL
                                   DEFAULT (''),
                               count       INTEGER      NOT NULL
                                   DEFAULT (0),
                               PRIMARY KEY (
                                            year,
                                            month,
                                            country,
                                            countrycode,
                                            regionname
                                   )
                                   ON CONFLICT REPLACE
);


-- Table: monthlyurl
CREATE TABLE monthlyurl (
                            year  INTEGER NOT NULL
                                DEFAULT (0),
                            month INTEGER NOT NULL
                                DEFAULT (0),
                            url   TEXT    NOT NULL
                                DEFAULT (''),
                            count INTEGER NOT NULL
                                DEFAULT (0),
                            PRIMARY KEY (
                                         year,
                                         month,
                                         url
                                )
                                ON CONFLICT REPLACE
);


COMMIT TRANSACTION;
PRAGMA foreign_keys = on;
