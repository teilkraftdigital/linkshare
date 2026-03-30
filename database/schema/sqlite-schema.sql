CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "buckets"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "color" varchar not null default 'gray',
  "is_inbox" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "color" varchar not null default 'gray',
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE UNIQUE INDEX "tags_slug_unique" on "tags"("slug");
CREATE TABLE IF NOT EXISTS "link_tag"(
  "link_id" integer not null,
  "tag_id" integer not null,
  foreign key("link_id") references "links"("id") on delete cascade,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("link_id", "tag_id")
);
CREATE TABLE IF NOT EXISTS "links"(
  "id" integer primary key autoincrement not null,
  "url" varchar not null,
  "title" varchar not null,
  "description" text,
  "notes" text,
  "bucket_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "favicon_url" varchar,
  foreign key("bucket_id") references "buckets"("id") on delete restrict
);
CREATE TABLE IF NOT EXISTS "media"(
  "id" integer primary key autoincrement not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  "uuid" varchar,
  "collection_name" varchar not null,
  "name" varchar not null,
  "file_name" varchar not null,
  "mime_type" varchar,
  "disk" varchar not null,
  "conversions_disk" varchar,
  "size" integer not null,
  "manipulations" text not null,
  "custom_properties" text not null,
  "generated_conversions" text not null,
  "responsive_images" text not null,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "media_model_type_model_id_index" on "media"(
  "model_type",
  "model_id"
);
CREATE UNIQUE INDEX "media_uuid_unique" on "media"("uuid");
CREATE INDEX "media_order_column_index" on "media"("order_column");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(5,'2026_03_27_235418_create_buckets_table',1);
INSERT INTO migrations VALUES(6,'2026_03_28_122116_create_tags_table',2);
INSERT INTO migrations VALUES(7,'2026_03_28_214202_create_link_tag_table',3);
INSERT INTO migrations VALUES(8,'2026_03_28_214201_create_links_table',3);
INSERT INTO migrations VALUES(9,'2026_03_29_212754_create_media_table',4);
INSERT INTO migrations VALUES(10,'2026_03_29_224430_add_favicon_url_to_links_table',5);
