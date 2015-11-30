CREATE TABLE "link" (
	"id" serial primary key,
	"url" varchar(255) DEFAULT NULL,
	"hits" integer,
	"permalink" character varying DEFAULT NULL UNIQUE,
	"date" timestamp DEFAULT current_timestamp
);
