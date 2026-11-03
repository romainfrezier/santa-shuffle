"""Run with python3 -m unittest discover -s .github/scripts."""

import unittest

from validate_release_tag import is_stable


class ReleaseTagTest(unittest.TestCase):
    def test_stable_tags(self):
        for tag in ["v0.0.0", "v0.1.0", "v1.2.3", "v12.30.400"]:
            with self.subTest(tag=tag):
                self.assertTrue(is_stable(tag))

    def test_prereleases_never_move_latest(self):
        for tag in ["v1.2.3-rc.1", "v0.1.0-beta", "v1.2.3-0", "v1.2.3-01a"]:
            with self.subTest(tag=tag):
                self.assertFalse(is_stable(tag))

    def test_malformed_tags_cannot_publish(self):
        for tag in ["", "1.2.3", "v1.2", "v01.2.3", "v1.02.3", "v1.2.03",
                    "v1.2.3-01", "v1.2.3-", "v1.2.3-rc..1", "v1.2.3+build.1",
                    "v1.2.3\n", "v1.2.3/other", "v1.2.3;echo bad", "v1.2.3-" + "a" * 122]:
            with self.subTest(tag=tag):
                with self.assertRaises(ValueError):
                    is_stable(tag)


if __name__ == "__main__":
    unittest.main()
