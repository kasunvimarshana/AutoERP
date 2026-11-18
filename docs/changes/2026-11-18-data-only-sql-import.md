# Data-only SQL import from database backup

**Date:** 2026-11-18

Created `C:\Users\Sadheera\Downloads\DB Bcakup\deziutge_auto2 - Copy (2) - data only.sql` from the supplied phpMyAdmin backup. The new file preserves all 704 `INSERT` statements, including their original column lists and values, for 138 tables with data. It omits table creation, indexes, constraints, and other DDL. The original backup was not changed.

The import file sets the source SQL mode, time zone, and character set. It temporarily disables foreign key checks while loading the dump's table order, then restores the previous session value. It is intended for an empty database whose schema matches the backup; existing rows can conflict with the original primary keys.

Verification: counted 704 `INSERT` statements in both files; found no DDL statements or uncaptured data lines in the output. No database import was run. No application code or schema was changed.
