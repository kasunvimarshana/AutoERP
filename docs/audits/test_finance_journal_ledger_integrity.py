"""Dependency-free tests for the SELECT-only Finance journal/ledger audit.

Run: python -m unittest -v test_finance_journal_ledger_integrity.py
Fixtures are disposable SQLite memory; no application/production database access.
"""

from __future__ import annotations

import sqlite3
import unittest
from pathlib import Path

SQL = Path(__file__).with_name('finance-journal-ledger-integrity.sql')
SCHEMA = """
CREATE TABLE finance_journal_entries (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    journal_number TEXT, status TEXT, total_debit NUMERIC, total_credit NUMERIC
);
CREATE TABLE finance_journal_lines (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    journal_entry_id INTEGER, account_id INTEGER, debit NUMERIC, credit NUMERIC
);
CREATE TABLE finance_ledger_entries (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    journal_entry_id INTEGER, journal_line_id INTEGER, account_id INTEGER,
    debit NUMERIC, credit NUMERIC
);
"""


def statements() -> list[str]:
    content = '\n'.join(line for line in SQL.read_text(encoding='utf-8').splitlines()
                        if not line.lstrip().startswith('--'))
    return [s.strip() for s in content.split(';') if s.strip()]


def fixture() -> sqlite3.Connection:
    db = sqlite3.connect(':memory:')
    db.row_factory = sqlite3.Row
    db.executescript(SCHEMA)
    db.execute("INSERT INTO finance_journal_entries VALUES (1,1,NULL,'J-001','posted',100,100)")
    db.executemany('INSERT INTO finance_journal_lines VALUES (?,?,?,?,?,?,?)', [
        (1,1,None,1,101,100,0), (2,1,None,1,201,0,100),
    ])
    db.executemany('INSERT INTO finance_ledger_entries VALUES (?,?,?,?,?,?,?,?)', [
        (1,1,None,1,1,101,100,0), (2,1,None,1,2,201,0,100),
    ])
    return db


class FinanceLedgerIntegrityAuditTest(unittest.TestCase):
    def setUp(self) -> None:
        self.db = fixture()
        self.sql = statements()
        self.assertEqual(3, len(self.sql))

    def tearDown(self) -> None:
        self.db.close()

    def query(self, index: int) -> list[sqlite3.Row]:
        def read_only(action: int, *unused: object) -> int:
            return (sqlite3.SQLITE_OK if action in {
                sqlite3.SQLITE_SELECT, sqlite3.SQLITE_READ, sqlite3.SQLITE_FUNCTION,
            } else sqlite3.SQLITE_DENY)
        self.db.set_authorizer(read_only)
        try:
            return self.db.execute(self.sql[index]).fetchall()
        finally:
            self.db.set_authorizer(None)

    def test_valid_journal_has_no_finding(self) -> None:
        self.assertEqual([], self.query(0))
        self.assertEqual([], self.query(1))
        self.assertEqual([], self.query(2))

    def test_read_only_statements_can_execute(self) -> None:
        for index, statement in enumerate(self.sql):
            self.assertTrue(statement.upper().startswith(('SELECT ', 'WITH ')))
            self.query(index)

    def test_balanced_but_wrong_header_is_flagged(self) -> None:
        self.db.execute('UPDATE finance_journal_entries SET total_debit=90,total_credit=90 WHERE id=1')
        flagged = self.query(0)
        self.assertEqual([1], [row['journal_id'] for row in flagged])
        self.assertEqual(100, flagged[0]['document_debit'])
        self.assertEqual(90, flagged[0]['header_debit'])

    def test_missing_ledger_entry_and_missing_lines_are_flagged(self) -> None:
        self.db.execute('DELETE FROM finance_ledger_entries WHERE id=2')
        self.assertEqual([1], [row['journal_id'] for row in self.query(0)])
        self.assertEqual(1, self.query(0)[0]['ledger_entry_count'])
        self.db.execute('DELETE FROM finance_journal_lines')
        self.assertEqual(0, self.query(0)[0]['document_line_count'])

    def test_wrong_line_account_and_amount_are_flagged(self) -> None:
        self.db.execute('UPDATE finance_ledger_entries SET account_id=999 WHERE id=1')
        self.assertEqual('account_mismatch', self.query(1)[0]['review_reason'])
        self.db.execute('UPDATE finance_ledger_entries SET account_id=101,debit=99 WHERE id=1')
        self.assertEqual('amount_mismatch', self.query(1)[0]['review_reason'])

    def test_invalid_signs_and_zero_lines_are_flagged(self) -> None:
        self.db.execute('UPDATE finance_ledger_entries SET debit=-1 WHERE id=1')
        self.assertEqual('amount_mismatch', self.query(1)[0]['review_reason'])
        self.assertEqual(1, self.query(0)[0]['invalid_entry_count'])
        self.db.execute('UPDATE finance_journal_lines SET debit=-1 WHERE id=1')
        self.assertEqual('invalid_ledger_amount', self.query(1)[0]['review_reason'])
        self.db.execute('UPDATE finance_ledger_entries SET debit=0,credit=0 WHERE id=1')
        self.db.execute('UPDATE finance_journal_lines SET debit=0,credit=0 WHERE id=1')
        self.assertEqual('invalid_ledger_amount', self.query(1)[0]['review_reason'])

    def test_cross_tenant_ledger_reference_is_flagged(self) -> None:
        self.db.execute('UPDATE finance_ledger_entries SET tenant_id=2 WHERE id=1')
        self.assertEqual('missing_or_cross_tenant_journal', self.query(1)[0]['review_reason'])
        self.assertEqual([1], [row['journal_id'] for row in self.query(0)])

    def test_organization_mismatch_is_detected_including_null(self) -> None:
        self.db.execute('UPDATE finance_ledger_entries SET organization_unit_id=2 WHERE id=1')
        self.assertEqual('organization_mismatch', self.query(1)[0]['review_reason'])

    def test_duplicate_ledger_line_and_missing_identical_line_cannot_cancel_out(self) -> None:
        # Both journal lines have equal amounts and account. Report 1 can see
        # the same number and amounts, while report 2 sees only valid entries.
        self.db.execute("INSERT INTO finance_journal_lines VALUES (3,1,NULL,1,101,100,0)")
        self.db.execute("INSERT INTO finance_journal_lines VALUES (4,1,NULL,1,201,0,100)")
        self.db.execute("INSERT INTO finance_ledger_entries VALUES (3,1,NULL,1,1,101,100,0)")
        self.db.execute("INSERT INTO finance_ledger_entries VALUES (4,1,NULL,1,4,201,0,100)")
        self.db.execute('UPDATE finance_journal_entries SET total_debit=200,total_credit=200 WHERE id=1')
        self.assertEqual([], self.query(0))
        self.assertEqual([], self.query(1))
        rows = self.query(2)
        self.assertEqual([(1, 2), (3, 0)],
                         [(row['journal_line_id'], row['matching_ledger_entry_count']) for row in rows])

    def test_missing_ledger_line_detected_independently_from_header_totals(self) -> None:
        self.db.execute('DELETE FROM finance_ledger_entries WHERE id=2')
        rows = self.query(2)
        self.assertEqual([(2, 0)],
                         [(row['journal_line_id'], row['matching_ledger_entry_count']) for row in rows])

    def test_reversed_journal_is_audited_without_double_posting(self) -> None:
        self.db.execute("UPDATE finance_journal_entries SET status='reversed' WHERE id=1")
        self.assertEqual([], self.query(0))
        self.db.execute('UPDATE finance_ledger_entries SET credit=90 WHERE id=2')
        self.assertEqual([1], [row['journal_id'] for row in self.query(0)])


if __name__ == '__main__':
    unittest.main()
