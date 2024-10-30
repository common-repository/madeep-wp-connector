=== Madeep WP Connector == =
Tags: booking, reservations, dms
Requires at least: 5.0
Tested up to: 6.4.3
Stable tag: 0.4.5
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Madeep Booking Interface downloader

== Description ==
Plugin di interfaccia ai sistemi di prenotazione [Madeep](https://www.madeep.com,"Madeep") per DMS, Booking Engine ed Ecommerce.
Permette il downlaod e la sincronizzazione di tutte le informazioni (immagini, testi, offerte) configurate con creazione di pagine statiche e link alla prenotazione diretta.

DMS (Destination Management System)
* Elenco Strutture con link a pagina della singola struttura
* Dettaglio singola struttura ricettiva con link a pagina di prenotazione
* Elenco Offerte Speciali con link a pagina di prenotazione
* Dettaglio singola offerta speciale con link a pagina di prenotazione
* Elenco Fornitori di Servizi con link a pagina di singolo Fornitore
* Dettaglio singolo fornitore di servizi con link a pagina di prenotazione
* Elenco Servizi con link a pagina del singolo servizio
* Dettaglio singoli servizi con link a pagina di prenotazione

Booking engine (per strutture ricettive ed extra-ricettive)
* Elenco Offerte Speciali con link a pagina di prenotazione
* Dettaglio singola offerta speciale con link a pagina di prenotazione

Ecommerce (per fornitori di servizi come noleggi, escursioni, degustazioni, biglietteria, etc ...)
* Elenco Servizi con link a pagina del singolo servizio
* Dettaglio singoli servizi con link a pagina di prenotazione

== Frequently Asked Questions ==

= Richiede qualche servizio aggiuntivo? =

Il plugin necessita di un contratto attivo con la Madeep per il suo booking engine.

= Perché non vengono cancellate le immagini caricate dal DMS se ho WPML attivo? =

Per il caricamento delle immagini con WPML attivo, é necessario disattivare la traduzione dei "media files".

== Changelog ==

= 0.4.5 =
* Risolto un problema con la dicitura dei testi personalizzati

= 0.4.4 =
* Aggiunti i placeholders per telefono, email, indirizzo e altri per gli ecommerce

= 0.4.3 =
* Corretto il contenuto di alcuni campi seguendo le modifiche avvenute nel XML

= 0.3.7 =
* Rimosso placeholder obsoleto %info%

= 0.3.6 =
* Risolto un problema di compatibilitá con le ultime versioni di WPML
* Risolto un problema con WP-Miltilang in combinazione con altri plugins

= 0.3.5 =
* Aggiunti placeholders per l'indirizzo di incontro per i servizi
* Risolto bug che affettava la sincronizzazione dei singoli servizi dalla lista dei post

= 0.3.4 =
* Aggiunto lo scarico dei filtri, dove disponibili.
* Ora il template replica anche il postType
* Aggiunti i placeholders per filtri e schede personalizzate

= 0.3.3 =
* Ora il cron non dovrebbe piú bloccarsi in caso la sincronizzazione richieda piú tempo del previsto per via della quantitá di dati scaricati (Nb. é solo un set_time_limit, quindi altre cose potrebbero ancora mandarlo in timeout... impostazioni php, apache, ecc.).

= 0.3.2 =
* Ora nell'aggiornamento degli hotels, i tag manuali non vengono sovrascritti.

= 0.3.1 =
* Migliorata la gestione dei tag.
* Pagine senza dati minimi sono rese bozze in automatico.
* Cancellamento immagini prima del aggiornamento.
* Modifica alla gestione delle stringhe in lingua fisse e aggiunta di file con traduzioni.
* Possibilitá di sincronizzare singole pagine direttamente dalla lista degli articoli.
* Altro che non ricordo.

= 0.3.0 =
* Rivisto l'approccio alla generazione delle pagine multilingua dando piú elasticitá al sistema.
* Aggiunti alla pagina di Debug i bottoni per resettare le pagine create dal plugin e il contenuto delle tabelle del plugin.
* Cambiato il funzionamento del CRON per evitare problemi di compatibilitá con altri plugins.
* Aggiunte alcune funzionalitá informative nelle pagine dei templates e categorie.
* Altro che non ricordo.

= 0.2.13 =
* Fix peroblema nelle categorie.

= 0.2.12 =
* Aggiunto il supporto a piú lingue.

= 0.2.11 =
* Fix per "postToDraft" per hotel singolo.

= 0.2.10 =
* Aggiunta pagina per il debug.

= 0.2.9 =
* Aggiunta Gallery, latitudine e longitudine.
* Aggiunta compatibilitá con WPML.
* Rimossa la generazione delle liste.
* Fixes .

= 0.2.8 =
* Risolto problema di versioni.

= 0.2.7 =
* Fix creazione pagine multiple per i servizi.

= 0.2.6 =
* Fix creazione multipagine.
* Fix per "postToDraft" per i servizi.

= 0.2.5 =
* Fix per il fix del "postToDraft" in modo DMS.

= 0.2.4 =
* Fix per "postToDraft" in modo DMS.
* Se l'offerta o servizio non hanno titolo, il post non viene creato

= 0.2.3 =
* Al posto della sostituzione della pagina, ora viene aggiornato solo il contenuto di esse.

= 0.2.2 =
* Aggiunti i nomi di Hotel/E-commerce come tag alle pagine di Servizi/Offerte.
* Pagine di Offerte/Servizi non importati nel ultimo aggiornamento vengono rese "bozze".

= 0.2.1 =
* Aggiustamento di versione.

= 0.2 =
* Aggiustamenti per i placeholders e aggiunte.
* Chiamata XML rivista.

= 0.1.1 =
* Alcuni fix.
* Readm migliorato.

= 0.1 =
* Release iniziale.

== Upgrade Notice ==

= 0.4.5 =
* Risolto un problema con la dicitura dei testi personalizzati

= 0.4.4 =
* Aggiunti i placeholders per telefono, email, indirizzo e altri per gli ecommerce

= 0.4.3 =
* Corretto il contenuto di alcuni campi seguendo le modifiche avvenute nel XML

= 0.3.7 =
* Rimosso placeholder obsoleto %info%

= 0.3.6 =
* Risolto un problema di compatibilitá con le ultime versioni di WPML
* Risolto un problema con WP-Miltilang in combinazione con altri plugins

= 0.3.5 =
* Aggiunti placeholders per l'indirizzo di incontro per i servizi
* Risolto bug che affettava la sincronizzazione dei singoli servizi dalla lista dei post

= 0.3.4 =
* Aggiunto lo scarico dei filtri, dove disponibili.
* Ora il template replica anche il postType
* Aggiunti i placeholders per filtri e schede personalizzate

= 0.3.3 =
* Ora il cron non dovrebbe piú bloccarsi in caso la sincronizzazione richieda piú tempo del previsto per via della quantitá di dati scaricati (Nb. é solo un set_time_limit, quindi altre cose potrebbero ancora mandarlo in timeout... impostazioni php, apache, ecc.).

= 0.3.2 =
* Ora nell'aggiornamento degli hotels, i tag manuali non vengono sovrascritti.

= 0.3.1 =
* Migliorata la gestione dei tag.
* Pagine senza dati minimi sono rese bozze in automatico.
* Cancellamento immagini prima del aggiornamento.
* Modifica alla gestione delle stringhe in lingua fisse e aggiunta di file con traduzioni.
* Possibilitá di sincronizzare singole pagine direttamente dalla lista degli articoli.
* Altro che non ricordo.

= 0.3.0 =
* Rivisto l'approccio alla generazione delle pagine multilingua dando piú elasticitá al sistema.
* Aggiunti alla pagina di Debug i bottoni per resettare le pagine create dal plugin e il contenuto delle tabelle del plugin.
* Cambiato il funzionamento del CRON per evitare problemi di compatibilitá con altri plugins.
* Aggiunte alcune funzionalitá informative nelle pagine dei templates e categorie.
* Altro che non ricordo.

= 0.2.13 =
* Fix peroblema nelle categorie.

= 0.2.12 =
* Aggiunto il supporto a piú lingue.

= 0.2.11 =
* Fix per "postToDraft" per hotel singolo.

= 0.2.10 =
* Aggiunta pagina per il debug.

= 0.2.9 =
* Aggiunta Gallery, latitudine e longitudine.
* Aggiunta compatibilitá con WPML.
* Rimossa la generazione delle liste.
* Fixes .

= 0.2.8 =
* Risolto problema di versioni.

= 0.2.7 =
* Fix creazione pagine multiple per i servizi.

= 0.2.6 =
* Fix creazione multipagine.
* Fix per "postToDraft" per i servizi.

= 0.2.5 =
* Fix per il fix del "postToDraft" in modo DMS.

= 0.2.4 =
* Fix per "postToDraft" in modo DMS.
* Se l'offerta o servizio non hanno titolo, il post non viene creato

= 0.2.3 =
* Al posto della sostituzione della pagina, ora viene aggiornato solo il contenuto di esse.

= 0.2.2 =
* Aggiunti i nomi di Hotel/E-commerce come tag alle pagine di Servizi/Offerte.
* Pagine di Offerte/Servizi non importati nel ultimo aggiornamento vengono rese "bozze".

= 0.2.1 =
* Aggiustamento di versione.

= 0.2 =
* Aggiustamenti per i placeholders e aggiunte.
* Chiamata XML rivista.

= 0.1.1 =
* Alcuni fix.
* Readm migliorato.
