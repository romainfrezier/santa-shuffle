"""Validate Docker-compatible SemVer tags before permitting publication."""

import os
import re


def is_stable(tag: str) -> bool:
    number = r"(?:0|[1-9][0-9]*)"
    identifier = rf"(?:{number}|[0-9]*[A-Za-z-][0-9A-Za-z-]*)"
    pattern = rf"v{number}\.{number}\.{number}(?:-{identifier}(?:\.{identifier})*)?"
    if len(tag) > 128 or not re.fullmatch(pattern, tag):
        raise ValueError("Expected a Docker-compatible vMAJOR.MINOR.PATCH[-prerelease] tag without build metadata.")
    return "-" not in tag


if __name__ == "__main__":
    stable = is_stable(os.environ["RELEASE_TAG"])
    with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as output:
        output.write(f"image=ghcr.io/{os.environ['REPOSITORY'].lower()}\n")
        output.write(f"stable={str(stable).lower()}\n")
