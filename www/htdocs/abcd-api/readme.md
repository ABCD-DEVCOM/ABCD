# ABCD REST API Endpoints

All current API endpoints utilize the **GET** HTTP method. The base URL path for all requests is `/abcd-api/`.

---

## 1. Status and Health Check

Use this endpoint to verify if the API is online and responding correctly.

**Endpoint:** `GET /abcd-api/`

**Action:** Returns a simple welcome message confirming the API is active.

---

## 2. Database Discovery

These public routes do not require authentication keys. They are used to list exposed database configurations.

**Endpoint:** `GET /abcd-api/databases`

**Action:** Returns a list of all exposed databases (e.g., `marc`, `dubcore`) along with their descriptions.

**Endpoint:** `GET /abcd-api/databases/{database_name}`

**Action:** Returns specific configuration details for a single database.

**Example:** `/abcd-api/databases/marc`

---

## 3. Record Retrieval and Search

**Security Notice:** If the requested database is configured with `restricted` access, you **must** include a valid API key in the request header using `X-API-Key`. If the database access level is `public`, the key is not required.

**Endpoint:** `GET /abcd-api/records/{database_name}`

**Action:** Searches and lists records within a specific database.

**Supported URL Parameters:**

* **q**: The search expression. It accepts mapped fields like `q=author:"silva"` or `q=$` to retrieve all records. If omitted, it defaults to searching everything (`$`).


* **limit**: The number of records to return per page. For security and performance, this is strictly capped at a maximum of 100 records.


* **from**: The starting index for the result set, used for pagination.



**Search Examples:**

* `/abcd-api/records/marc` (Returns the first 10 records by default)


* `/abcd-api/records/marc?q=author:"machado"&limit=50&from=1`


**Endpoint:** `GET /abcd-api/records/{database_name}/{mfn}`

**Action:** Retrieves a single, exact record based on its Master File Number (MFN). The MFN parameter undergoes strict security validation and only accepts alphanumeric characters.

**Example:** `/abcd-api/records/dubcore/1`