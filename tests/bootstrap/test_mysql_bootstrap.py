"""Dependency-free fail-closed checks for AutoERP's MySQL PHPUnit bootstrap.

Run from the repository root:
    python -B -m unittest discover -s tests/bootstrap -p 'test_*.py' -v

These checks execute a copy of the *actual* PHP bootstrap with a temporary
stub Composer autoloader. They never open a database or execute migrations.
"""

from __future__ import annotations

import json
import os
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path


BOOTSTRAP = Path(__file__).resolve().with_name("mysql.php")
SENSITIVE_DB_ENV = ("DB_CONNECTION", "DB_DATABASE", "DB_HOST", "DB_URL")


class MySqlBootstrapSafetyTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.php = shutil.which("php")
        if cls.php is None:
            raise RuntimeError("PHP is required to verify the MySQL test safety bootstrap.")

        cls.temp = tempfile.TemporaryDirectory(prefix="autoerp-mysql-bootstrap-")
        root = Path(cls.temp.name)
        (root / "tests" / "bootstrap").mkdir(parents=True)
        (root / "vendor").mkdir()
        cls.script = root / "tests" / "bootstrap" / "mysql.php"
        shutil.copyfile(BOOTSTRAP, cls.script)
        (root / "vendor" / "autoload.php").write_text(
            "<?php echo json_encode(["
            "'env' => array_intersect_key($_ENV, array_flip(['DB_CONNECTION','DB_DATABASE','DB_HOST','DB_URL'])),"
            "'server' => array_intersect_key($_SERVER, array_flip(['DB_CONNECTION','DB_DATABASE','DB_HOST','DB_URL'])),"
            "'process' => [getenv('DB_CONNECTION'), getenv('DB_DATABASE'), getenv('DB_HOST'), getenv('DB_URL')]"
            "]);",
            encoding="utf-8",
        )

    @classmethod
    def tearDownClass(cls) -> None:
        cls.temp.cleanup()

    def run_bootstrap(self, **overrides: str) -> subprocess.CompletedProcess[str]:
        env = os.environ.copy()
        for key in SENSITIVE_DB_ENV:
            env.pop(key, None)
        env.update(overrides)

        return subprocess.run(
            [self.php, str(self.script)],
            env=env,
            capture_output=True,
            text=True,
            check=False,
            timeout=15,
        )

    def assert_rejected(self, message: str, **env: str) -> None:
        result = self.run_bootstrap(**env)
        self.assertNotEqual(0, result.returncode)
        self.assertIn(message, result.stderr)

    def assert_pinned(self, connection: str, database: str, host: str) -> None:
        result = self.run_bootstrap(
            DB_CONNECTION=connection, DB_DATABASE=database, DB_HOST=host,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        data = json.loads(result.stdout)
        expected = {
            "DB_CONNECTION": connection,
            "DB_DATABASE": database,
            "DB_HOST": host,
            "DB_URL": "",
        }
        self.assertEqual(expected, data["env"])
        self.assertEqual(expected, data["server"])
        self.assertEqual([connection, database, host, ""], data["process"])

    def test_rejects_non_mysql_driver(self) -> None:
        self.assert_rejected(
            "DB_CONNECTION=mysql",
            DB_CONNECTION="sqlite", DB_DATABASE="app_test", DB_HOST="localhost",
        )

    def test_rejects_unsafe_database_name(self) -> None:
        self.assert_rejected(
            "ending with _test",
            DB_CONNECTION="mysql", DB_DATABASE="production", DB_HOST="localhost",
        )

    def test_rejects_explicit_database_url(self) -> None:
        self.assert_rejected(
            "DB_URL to be empty",
            DB_CONNECTION="mysql", DB_DATABASE="app_test",
            DB_HOST="localhost", DB_URL="mysql://production",
        )

    def test_requires_explicit_host_instead_of_inheriting_dotenv(self) -> None:
        self.assert_rejected(
            "explicitly selected DB_HOST",
            DB_CONNECTION="mysql", DB_DATABASE="app_test",
        )

    def test_pins_valid_mysql_environment(self) -> None:
        self.assert_pinned("mysql", "app_test", "127.0.0.1")

    def test_pins_valid_mariadb_environment(self) -> None:
        self.assert_pinned("mariadb", "app_test", "localhost")

    def test_preserves_case_sensitive_database_identifier(self) -> None:
        self.assert_pinned("mysql", "AutoERP_TEST", "localhost")


if __name__ == "__main__":
    unittest.main()
