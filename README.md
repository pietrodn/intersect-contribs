# Intersect Contribs

## Purpose

**Intersect Contribs** intersects the contribution history of two users on a Wikimedia project to find which pages have been edited by both of them.

It's useful to discover sockpuppets, double votes and false accounts.

## Hosting

The tool is currently hosted on [Wikimedia Tool Labs](https://toolforge.org/) at the following address:
https://intersect-contribs.toolforge.org/

It was previously hosted on [Wikimedia Toolserver](https://meta.wikimedia.org/wiki/Toolserver).

## Technical details

The tool is a simple script written in PHP.
The tool directly accesses a [replica of the Wikimedia database](https://wikitech.wikimedia.org/wiki/Help:Tool_Labs#Database_access) to fetch the contributions of the users; it also uses the [MediaWiki API](https://www.mediawiki.org/wiki/API:Main_page) to obtain information about the namespaces of projects.

## Contacts

You can send any request, bug report, suggestions for improvements or pull request here on GitHub.
Alternatively, you can reach me on [Meta Wikimedia](https://meta.wikimedia.org/wiki/User:Pietrodn).

## License

This software is licensed under the [GNU General Public License, Version 3](https://www.gnu.org/licenses/gpl.html).
You can find a copy if it in the `LICENSE` file.
