# ABCD REST API Endpoints

All current API endpoints utilize the **GET** HTTP method. The base URL path for all requests is `/abcd-api/`.

---

## 1. Status and Health Check

Use this endpoint to verify if the API is online and responding correctly.

**Endpoint:** `GET /abcd-api/`

**Action:** Returns a simple welcome message confirming the API is active.

---

## 2. Database Discovery

These public routes do not require authentication keys. They are used to list exposed database configurations and the available metadata output formats for each database.

**Endpoint:** `GET /abcd-api/databases`

**Action:** Returns a list of all exposed databases (e.g., `marc`, `dubcore`) along with their descriptions.

**Endpoint:** `GET /abcd-api/databases/{database_name}`

**Action:** Returns specific configuration details for a single database, including its CISIS version and the available formats (`native`, `dc`, etc.) configured via the visual mapper or manually created `.pft` files.

**Example:** `/abcd-api/databases/marc`

---

## 3. Record Retrieval and Search

**Security Notice:** If the requested database is configured with `restricted` access, you **must** include a valid API key in the request header using `X-API-Key`. If the database access level is `public`, the key is not required.

### 3.1 Fetching a List of Records / Searching

**Endpoint:** `GET /abcd-api/records/{database_name}`

**Action:** Searches and lists records within a specific database. It will automatically fallback to sequential fetching if the underlying database inverted file (dictionary) is not yet generated or responds with a fatal error on global `$` searches.

**Supported URL Parameters:**

*   **q**: The search expression. It accepts mapped fields like `q=author:"silva"` or `q=$` to retrieve all records. If omitted, it defaults to searching everything (`$`).
*   **limit**: The number of records to return per page. For security and performance, this is strictly capped at a maximum of 100 records. (Default: 10)
*   **from**: The starting index for the result set, used for pagination. (Default: 0)
*   **format**: The metadata output format. Accepts `dc` (Dublin Core JSON, default) or `native` (raw ISIS FDT tags). Note that the `dc` format requires an active mapping (`.i2x` or `.pft`) generated via the ABCD REST API Manager interface.
*   **debug**: Set to `?debug=1` to intercept the raw WXIS execution paths, commands, and XML responses for troubleshooting.

**Search Examples:**

*   `/abcd-api/records/marc` (Returns the first 10 records by default in Dublin Core format)
*   `/abcd-api/records/marc?q=author:"machado"&limit=50&from=1`
*   `/abcd-api/records/dubcore?format=native` (Returns records using native ISIS structural tags)

### 3.2 Fetching a Single Record by ID

**Endpoint:** `GET /abcd-api/records/{database_name}/{mfn}`

**Action:** Retrieves a single, exact record based on its physical Master File Number (MFN) bypassing the CISIS dictionary. The MFN parameter undergoes strict security validation and only accepts integer characters.

**Supported URL Parameters:**

*   **format**: `dc` (default) or `native`.
*   **debug**: `1` (Intercepts and prints WXIS raw output).

**Examples:**

*   `/abcd-api/records/dubcore/1` (Fetches MFN 1 in Dublin Core JSON format)
*   `/abcd-api/records/spectrum/25?format=native` (Fetches MFN 25 in Native ISIS JSON format)
