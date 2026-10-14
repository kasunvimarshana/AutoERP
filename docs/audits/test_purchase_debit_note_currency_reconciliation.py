"""Dependency-free regression tests for the read-only Purchase currency audit.

Run from the repository root:
    python -m unittest discover -s docs/audits -p 'test_*.py' -v

This uses an isolated in-memory SQLite fixture. It never opens application or
production database connections and is NOT proof of MySQL/Finance correctness.
"""

from __future__ import annotations

import sqlite3
import unittest
from pathlib import Path

AUDIT_SQL = Path(__file__).with_name("purchase-debit-note-currency-reconciliation.sql")

SCHEMA = """
CREATE TABLE purchase_debit_notes (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    supplier_id INTEGER, supplier_type TEXT, currency_id INTEGER,
    purchase_return_id INTEGER, amount NUMERIC, allocated_amount NUMERIC,
    remaining_amount NUMERIC
);
CREATE TABLE purchase_returns (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, currency_id INTEGER,
    source_type TEXT, source_id INTEGER
);
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    party_id INTEGER, party_type TEXT, direction TEXT, currency_id INTEGER
);
CREATE TABLE invoice_credit_allocations (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, organization_unit_id INTEGER,
    credit_source_type TEXT, credit_source_id INTEGER, invoice_id INTEGER,
    allocated_amount NUMERIC
);
CREATE TABLE goods_receipt_notes (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, purchase_order_id INTEGER
);
CREATE TABLE purchase_orders (
    id INTEGER PRIMARY KEY, tenant_id INTEGER, currency_id INTEGER
);
"""


def sql_statements() -> list[str]:
    cleaned = "\n".join(
        line for line in AUDIT_SQL.read_text(encoding="utf-8").splitlines()
        if not line.lstrip().startswith("--")
    )
    return [part.strip() for part in cleaned.split(";") if part.strip()]


def fixture() -> sqlite3.Connection:
    db = sqlite3.connect(":memory:")
    db.row_factory = sqlite3.Row
    db.executescript(SCHEMA)
    # Each note is distinct. All but note 1 balance to their single allocations.
    notes = [
        (1, 1, 1, 11, "supplier", None, 1, 100, 20, 80),
        (2, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
        (3, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
        (4, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
        (5, 1, None, 11, "supplier", 2, None, 30, 10, 20),
        (6, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
        (7, 1, 1, 11, "customer", 2, None, 30, 10, 20),
        (8, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
        (9, 1, 1, 11, "supplier", 2, None, 30, 10, 20),
    ]
    db.executemany("INSERT INTO purchase_debit_notes VALUES (?,?,?,?,?,?,?,?,?,?)", notes)
    invoices = [
        (1, 1, 1, 11, "supplier", "inbound", 2),
        (2, 1, 1, 11, "supplier", "inbound", 3),
        (3, 1, 1, 11, "supplier", "inbound", 2),
        (4, 1, 2, 11, "supplier", "inbound", 2),
        (5, 1, 1, 11, "supplier", "inbound", 2),
        (6, 1, 1, 11, "supplier", "inbound", 2),
        (7, 1, 1, 11, "customer", "inbound", 2),
        (8, 1, 1, None, "supplier", "inbound", 2),
        (9, 1, 1, 11, "supplier", "outbound", 2),
    ]
    db.executemany("INSERT INTO invoices VALUES (?,?,?,?,?,?,?)", invoices)
    allocations = [
        (1, 1, 1, "purchase_debit_note", 1, 1, 10),
        (2, 1, 1, "purchase_debit_note", 2, 2, 10),
        (3, 1, 1, "purchase_debit_note", 3, 3, 10),
        (4, 1, 2, "purchase_debit_note", 4, 4, 10),
        (5, 1, 1, "purchase_debit_note", 5, 5, 10),
        (6, 1, None, "purchase_debit_note", 6, 6, 10),
        (7, 1, 1, "purchase_debit_note", 7, 7, 10),
        (8, 1, 1, "purchase_debit_note", 8, 8, 10),
        (9, 1, 1, "purchase_debit_note", 9, 9, 10),
        # Tenant 2 must not acquire the document or invoice belonging to tenant 1.
        (10, 2, 1, "purchase_debit_note", 1, 1, 10),
    ]
    db.executemany("INSERT INTO invoice_credit_allocations VALUES (?,?,?,?,?,?,?)", allocations)
    db.execute("INSERT INTO purchase_returns VALUES (1,1,NULL,'goods_receipt_note',1)")
    db.execute("INSERT INTO goods_receipt_notes VALUES (1,1,1)")
    db.execute("INSERT INTO purchase_orders VALUES (1,1,2)")
    db.commit()
    return db


class PurchaseDebitNoteCurrencyAuditTest(unittest.TestCase):
    def setUp(self) -> None:
        self.statements = sql_statements()
        self.assertEqual(5, len(self.statements), "The five audit reports must remain present")
        self.db = fixture()

    def tearDown(self) -> None:
        self.db.close()

    def report(self, index: int) -> list[sqlite3.Row]:
        # Explicit SQLite read-only authorizer, on top of the SQL SELECT-only check.
        def authorize(action: int, *unused: object) -> int:
            return (sqlite3.SQLITE_OK if action in
                    {sqlite3.SQLITE_SELECT, sqlite3.SQLITE_READ, sqlite3.SQLITE_FUNCTION}
                    else sqlite3.SQLITE_DENY)
        self.db.set_authorizer(authorize)
        try:
            return self.db.execute(self.statements[index]).fetchall()
        finally:
            self.db.set_authorizer(None)

    def test_all_audit_statements_are_read_only_and_executable(self) -> None:
        for index, statement in enumerate(self.statements):
            with self.subTest(report=index + 1):
                self.assertTrue(statement.upper().startswith("SELECT "))
                self.report(index)

    def test_unresolved_note_and_return_currencies(self) -> None:
        note = self.report(0)[0]
        self.assertEqual(9, note["note_count"])
        self.assertEqual(1, note["currency_unresolved_count"])
        self.assertEqual(1, note["unresolved_with_allocations_count"])
        ret = self.report(1)[0]
        self.assertEqual(1, ret["return_count"])
        self.assertEqual(1, ret["currency_unresolved_count"])

    def test_anomalies_cover_scope_supplier_currency_direction_and_tenant(self) -> None:
        actual = {row["allocation_id"]: row["review_reason"] for row in self.report(2)}
        expected = {
            1: "unresolved_currency",
            2: "currency_mismatch",
            4: "organization_mismatch",
            5: "organization_mismatch",  # NULL note org vs non-null invoice
            6: "organization_mismatch",  # NULL allocation org vs non-null invoice
            7: "supplier_mismatch",      # matching but invalid customer/customer roles
            8: "supplier_mismatch",      # NULL invoice party ID
            9: "invoice_direction_mismatch",
            10: "missing_purchase_debit_note",  # wrong tenant
        }
        self.assertEqual(expected, actual)

    def test_stored_note_balance_reconciles_with_invoice_credit_sum(self) -> None:
        result = self.report(3)
        self.assertEqual([1], [row["purchase_debit_note_id"] for row in result])
        self.assertEqual(20, result[0]["stored_allocated_amount"])
        self.assertEqual(10, result[0]["invoice_credit_allocation_sum"])

    def test_source_lineage_is_evidence_only(self) -> None:
        result = self.report(4)
        self.assertEqual([1], [row["purchase_debit_note_id"] for row in result])
        self.assertIsNone(result[0]["note_currency_id"])
        self.assertIsNone(result[0]["return_currency_id"])
        self.assertEqual(2, result[0]["order_currency_id"])


if __name__ == "__main__":
    unittest.main()
