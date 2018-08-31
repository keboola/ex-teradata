# Teradata Extractor

[![Build Status](https://travis-ci.com/keboola/ex-teradata.svg?branch=master)](https://travis-ci.com/keboola/ex-teradata)
[![Code Climate](https://codeclimate.com/github/keboola/ex-teradata/badges/gpa.svg)](https://codeclimate.com/github/keboola/ex-teradata)
[![Test Coverage](https://codeclimate.com/github/keboola/ex-teradata/badges/coverage.svg)](https://codeclimate.com/github/keboola/ex-teradata/coverage)

Keboola Connection Extractor for [Teradata](https://www.teradata.com/)

# Usage

The following parameters are required:

- `host` - IP address or Hostname for Teradata server
- `user` - User with correct access rights
- `#password` - Password for given User
- `database` - Database name

Configuration with custom query:

```json
{
    "parameters": {
        "db": {
            "host": "100.200.30.40",
            "user": "tduser",
            "#password": "tdpassword",
            "database": "tddatabase"
        },
        "name": "tablename",
        "query": "SELECT COUNT(*) c, column1 FROM tddatabase.tablename GROUP BY column1"
        "outputTable": "out.c-main.tablename",
        "incremental": false,
        "primaryKey": null
    }
}
``` 

Configuration with defined table:

```json
{
	"parameters": {
		"db": {
		    "host": "100.200.30.40",
		    "user": "tduser",
		    "#password": "tdpassword",
		    "database": "tddatabase"
		},
		"name": "test_1",
		"outputTable": "out.c-main.test-1",
		"incremental": false,
		"primaryKey": null,
		"table": {
			"schema": "tddatabase",
			"tableName": "test_1"
		}
	}
}
```


Configuration with defined columns:

```json
{
	"parameters": {
		"db": {
		    "host": "100.200.30.40",
		    "user": "tduser",
		    "#password": "tdpassword",
		    "database": "tddatabase"
		},
		"name": "test_1",
		"outputTable": "out.c-main.test-1",
		"incremental": false,
		"primaryKey": null,
		"table": {
			"schema": "tddatabase",
			"tableName": "test_1"
		},
		"columns": [
			"column1"
		]
	}
}
```

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/ex-teradata
cd my-component
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Create `.env` file:
```
TERADATA_HOST=35.158.41.16
TERADATA_USERNAME=dbc
TERADATA_PASSWORD=
TERADATA_DATABASE=ex_teradata_test
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
