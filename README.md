# WbmProductTypeFilter (Shopware 6.7)

Dieses Plugin erweitert Shopware 6 um zusätzliche Produktdaten aus einer externen Schnittstelle und stellt diese sowohl im **Admin-Backend** als auch im **Storefront** als filter- und suchfähige Information bereit.

## Kurzüberblick der Lösung

- Erweiterung der Produktdaten über eine eigene Extension-Entity (`wbm_product_type_extension`)
- Anzeige und Pflege im Admin unter **Produkt > Spezifikationen**
- Erweiterung der Admin-Produktliste:
  - zusätzliche Spalte `productType`
  - Suche findet Produkte anhand `productType`
  - Multi-Select-Filter für mehrere Produkttypen
- Erweiterung des Storefront-Listings:
  - Multi-Select-Filter `productType`
  - Optionen dynamisch über Aggregation (Elasticsearch/OpenSearch)
- Bonus: `productType` wird in der Storefront-Suche berücksichtigt

---

## Anforderungen & Umsetzung

### 1) Admin: Erweiterung der Produktdaten um neue Felder

**Ziel:** Shopware speichert zusätzliche Daten aus einer externen Quelle. Zunächst zwei Felder:

- `productIdFromApi` (Integer)  
  - wird im Tab **Spezifikationen** angezeigt
  - ist **nicht editierbar**
- `productType` (String)  
  - wird im Tab **Spezifikationen** angezeigt
  - ist **editierbar**

**Technische Entscheidung:**  
Die Daten werden **nicht als `customFields`** gespeichert, sondern als **eigene DAL-Entity**:

- Entity: `wbm_product_type_extension`
- Relation: `product (1) -> extension (1)` über `product_id`

**Warum diese Entscheidung:**
- sauberer, typisierter Datenlayer (DAL) statt “freies” Schema
- besser erweiterbar (bis zu 30 Felder sind vorgesehen)
- klare Datenhoheit und gute Query-/Indexierbarkeit
- Versionierung/Translations können gezielt gesteuert werden

---

### 2) Admin: Anpassung der Produktliste (Katalog → Produkte)

#### 2.1 Spalte `productType`
Die Produktliste erhält eine zusätzliche Spalte für `productType`.

**Entscheidung:**  
Die Liste bindet die Association `wbmProductTypeExtension` an und rendert den Wert in einer eigenen Column-Template-Erweiterung.

#### 2.2 Suche im Admin muss `productType` finden
Wenn der Admin in der Suche z. B. „Bücher“ eingibt, sollen Produkte mit `productType = Bücher` erscheinen.

**Entscheidung:**  
`productType` wird beim ES/OS Index-Build zusätzlich in `customSearchKeywords` angereichert (Index-optimiert), damit die bestehende Suchlogik der Produktliste den Wert zuverlässig berücksichtigt.

Umsetzungshinweis: Die Synchronisation der customSearchKeywords erfolgt performant als Bulk-Update (CASE/IN), nicht pro Produktzeile.

#### 2.3 Multi-Select-Filter für `productType`
- Filter erlaubt mehrere Typen gleichzeitig
- Optionen werden dynamisch aus der DB geladen

**Entscheidung:**  
Die Filteroptionen werden aus der Extension-Entity per Aggregation erzeugt:
- `Criteria.terms('types', 'productType')`
- daraus werden Options `{ id: type, name: type }` gebaut

**Warum:**  
- keine Hardcoded Werte
- skaliert mit vielen Produkten
- keine extra Admin-Konfiguration nötig

---

### 3) Storefront: Neuer Produktfilter im Listing

Im Storefront-Listing (Kategorie) wird ein neuer Multi-Select Filter angezeigt.

**Technische Entscheidung:**  
Der Filter nutzt die bestehende Shopware Listing-Filter-Architektur:

- `ProductListingCriteriaEvent`: Association(en) ergänzen (optional)
- `ProductListingCollectFilterEvent`: Filter + Aggregation hinzufügen

Die Optionen werden dynamisch über eine **TermsAggregation** erzeugt.

---

### 4) Bonus: Storefront-Suche berücksichtigt `productType`

**Entscheidung:**  
Für zuverlässige Suchtreffer wird `productType` im Index als eigenes Feld geführt und zusätzlich in `customSearchKeywords` angehängt.

Damit findet die Storefront-Suche Treffer, auch wenn das Feld selbst als `keyword` gemapped ist und die Fulltext-Suche primär auf Keywords basiert.

---

## Elasticsearch / OpenSearch: Indexierung & Mapping

### Ziel
- `wbmProductType` muss aggregierbar sein (Filter)
- Suche muss den Produkttyp finden können

### Umsetzung
- Mapping: `wbmProductType` wird als `keyword` gemapped
- Dokumentanreicherung: Beim Fetch der Dokumentdaten wird `wbmProductType` aus `wbm_product_type_extension` gemerged
- Sucherweiterung: `customSearchKeywords` wird pro Sprache um den Produkttyp ergänzt

**Warum `keyword`:**
- Aggregationen (Facets) funktionieren zuverlässig
- Filter sind performant, auch bei vielen Produkten

---

## Performance & Skalierung

Die Lösung ist so ausgelegt, dass sie auch bei großen Produktmengen stabil bleibt:

- Filteroptionen im Storefront über Aggregation (ES/OS) statt DB-Scanning
- `keyword` Mapping für schnelle Terms-Aggregations/Filters
- Erweiterungsdaten liegen in eigener Tabelle und sind sauber joinbar
- Keine teuren Runtime-Operationen pro Request außerhalb des Suchindexes
- Admin-Filteroptionen über Aggregation statt “distinct select” auf großen Tabellen (optional DB-Variante möglich)
- Updates der `custom_search_keywords` werden als Bulk-Statements ausgeführt (Clear per IN, Set per CASE), um DB-Roundtrips zu minimieren.

---

## Installation & Reindex

Plugin installieren/aktivieren

```bash
ddev exec bin/console plugin:refresh
ddev exec bin/console plugin:install WbmProductTypeFilter --activate --clearCache
```

Damit `es:index` und `es:admin:index` funktionieren, sollten in der `.env.local` folgende Variablen gesetzt sein:

```dotenv
OPENSEARCH_URL=opensearch:9200
SHOPWARE_ES_INDEXING_ENABLED=1
SHOPWARE_ES_ENABLED=1
SHOPWARE_ES_INDEX_PREFIX=wbm-
SHOPWARE_ES_THROW_EXCEPTION=1
ADMIN_OPENSEARCH_URL=opensearch:9200
SHOPWARE_ADMIN_ES_ENABLED=1
SHOPWARE_ADMIN_ES_REFRESH_INDICES=1
SHOPWARE_ADMIN_ES_INDEX_PREFIX=wbm-admin-
```

Reindex (wenn notwendig)

```bash
ddev exec bin/console dal:refresh:index
ddev exec bin/console es:index
ddev exec bin/console es:admin:index
```

**Hinweis:** Nach Änderungen am Mapping/Decorator ist ein Reindex erforderlich, damit wbmProductType zuverlässig im Index verfügbar ist.

---

## QA

Im Plugin sind QA vorgesehen:

- PHP-CS-Fixer (Code Style)
- PHPStan (Static Analysis)
- `lint:container` (DI / Autowiring Check)
- `lint:twig` nur für Storefront-Templates (Admin-Templates nutzen `{% parent %}` via Build-Tooling)

### Ein Command für alles (QA)
```bash
ddev exec bash -lc "cd custom/plugins/WbmProductTypeFilter && composer qa"
```