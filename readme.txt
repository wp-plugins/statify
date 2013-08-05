=== Statify ===
Contributors: sergej.mueller
Tags: stats, analytics, privacy, dashboard
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5RDDW9FEHGLG6
Requires at least: 3.0
Tested up to: 3.6
Stable tag: trunk



Besucherstatistik mit Schwerpunkten Datenschutz, Transparenz und Übersichtlichkeit. Ideal fürs Dashboard.



== Description ==

*Statify* verfolgt ein simples Ziel: Live-Zugriffszahlen des Blogs blitzschnell und kompakt zugänglich machen. Ohne Schnickschnack.


= Dashboard-Widget =
Weniger ist mehr: Das Statistik-Plugin präsentiert auf dem Admin-Dashboard den aktuellen Verlauf der Seitenaufrufe in Form eines interaktiven Diagramms. Der Zeitskala folgt jeweils eine Liste mit den häufigsten Verweisquellen (Referrer) und den meist aufgerufenen Zielseiten im Blog. Praktisch: Der Statistikzeitraum sowie die Listenlänge lassen sich direkt im Dashboard-Widget konfigurieren.


= Datenschutz =
Im unmittelbaren Vergleich zu Statistik-Diensten wie *Google Analytics* oder *WordPress.com Stats* verarbeitet und speichert *Statify* keinerlei personenbezogene Daten wie z.B. IP-Adressen. Absolute Datenschutzkonformität gepaart mit transparenter Arbeitsweise: Eine lokal in WordPress angelegte Datenbanktabelle besteht aus nur 4 Feldern (ID, Datum, Quelle, Ziel) und kann vom Administrator jederzeit eingesehen, bereinigt, geleert werden.


= Caching-Plugins =
Für die Kompatibilität mit Caching-Plugins wie [Cachify](http://wordpress.org/extend/plugins/cachify/) verfügt *Statify* über ein optional zuschaltbares Tracking via JavaScript-Snippet. Diese Methode erlaubt eine zuverlässige Zählung der gecachten Blogseiten.


= Filter =
*Statify* protokolliert jeden Seitenaufruf im WordPress-Frontend. Ausgeschlossen sind Preview-, Feed-, Ressourcen-Ansichten und Zugriffe durch angemeldete Nutzer. Mehr Einzelheiten zu Optionen und Funktionen im [Online-Handbuch](http://playground.ebiene.de/statify-wordpress-statistik/).


> #### Statify Chrome App
> Speziell für Inhaber und Administratoren mehrerer WordPress-Projekte wurde eine App für Google Chrome entwickelt: [Statify Chrome App](http://playground.ebiene.de/statify-wordpress-statistik/#chrome_app) - *Statify*-Statistiken an einer Stelle im Browser gesammelt dargestellt. Ab sofort kein lästiges Aufrufen der einzelnen Dashboards mehr. Statistikberichte verknüpfter Blogs in einem Fenster.


= Support =
Freundlich formulierte Fragen rund um das Plugin werden per E-Mail beantwortet.


= Systemanforderungen =
* PHP 5.2.4
* WordPress ab 3.4


= Unterstützung =
* Per [Flattr](https://flattr.com/donation/give/to/sergej.mueller)
* Per [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5RDDW9FEHGLG6)


= Handbuch =
* [Statify: Statistik für WordPress](http://playground.ebiene.de/statify-wordpress-statistik/)


= Website =
* [statify.de](http://statify.de)


= Autor =
* [Twitter](https://twitter.com/wpSEO)
* [Google+](https://plus.google.com/110569673423509816572 "Google+")
* [Plugins](http://wpcoder.de "Plugins")



== Changelog ==

= 1.2.3 =
* Zusätzliche Absicherung der PHP-Klassen vor direkten Aufrufen
* Ersatz für Deprecated [User Levels](http://codex.wordpress.org/Roles_and_Capabilities#User_Levels)

= 1.2.2 =
* No-Cache und No-Content Header für das optionale Zähl-JavaScript

= 1.2.1 =
* Zusätzliche Zeiträume (bis zu einem Jahr) für Statistik
* WordPress 3.4 als Systemanforderung

= 1.2 =
* Speziell für Chrome-Browser entwickelte [Statify App](http://playground.ebiene.de/statify-wordpress-statistik/#chrome_app)
* Fix für eingeführte XML-RPC-Schnittstelle

= 1.1 =
* WordPress 3.5 Support
* Schnittstelle via XML-RPC
* Refactoring der Code-Basis
* Überarbeitung der Online-Dokumentation
* Optionales Tracking via JavaScript für Caching-Plugins

= 1.0 =
* WordPress 3.4 Support
* [Offizielle Plugin-Website](http://statify.de "Statify WordPress Stats")
* Unkomprimierte Version des Source Codes

= 0.9 =
* Xmas Edition

= 0.8 =
* Unterstützung für WordPress 3.3
* Anzeige des Dashboard-Widgets auch für Autoren
* Direkter Link zu den Einstellungen auf dem Dashboard
* Filterung der Ziele/Referrer auf den aktuellen Tag

= 0.7 =
* Umsortierung der Statistiktage
* Umfärbung der Statistikmarkierung
* Ignorierung der XMLRPC-Requests

= 0.6 =
* WordPress 3.2 Unterstützung
* Support für WordPress Multisite
* Bereinigung überflüssiger URL-Parameter bei Zielseiten
* Interaktive Statistik mit weiterführenden Informationen

= 0.5 =
* Fix: Abfrage für fehlende Referrer im Dashboard Widget

= 0.4 =
* Statify geht online



== Screenshots ==

1. Statify Dashboard Widget
2. Statify Dashboard Widget Optionen
2. Statify Chrome App