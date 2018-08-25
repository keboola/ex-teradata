# Teradata Extractor

[![Build Status](https://travis-ci.com/keboola/ex-teradata.svg?branch=master)](https://travis-ci.com/keboola/ex-teradata)
[![Code Climate](https://codeclimate.com/github/keboola/ex-teradata/badges/gpa.svg)](https://codeclimate.com/github/keboola/ex-teradata)
[![Test Coverage](https://codeclimate.com/github/keboola/ex-teradata/badges/coverage.svg)](https://codeclimate.com/github/keboola/ex-teradata/coverage)

Keboola Connection Extractor for [Teradata](https://www.teradata.com/)

# Usage

The following parameters are required:

- `host` - IP address or Hostname for Teradata server
- `username` - User with correct access rights
- `#password` - Password for given User
- `database` - Database name

At least one table must be must be specified. Table definition might contain `query` parameter with SQL for complex data export e.g. aggregated tables or `columns` object with field `name` (database column name) for specifing exporting table columns. When specifiing both parameters, the query will be used. In case of leaving both parameters unset, all available columns will be extracted.

Sample configuration:

```json
{
    "parameters": {
        "db": {
            "host": "100.200.30.40",
            "username": "tduser",
            "#password": "tdpassword",
            "database": "tddatabase"
        },
        "tables": [
            {
                "name": "tablename",
                "incremental": false,
                "outputTable": "out.c-bucket.tablename"
            }
        ]
    }
}
``` 

Configuration with custom query:

```json
"tables": [
	{
		"name": "tablename",
		"incremental": false,
		"outputTable": "out.c-bucket.tablename",
		"query": "SELECT COUNT(*) c, column1 FROM tddatabase.tablename GROUP BY column1"
	}
]	
```

Configuration with defined columns:

```json
"tables": [
	{
		"name": "tablename",
		"incremental": false,
		"outputTable": "out.c-bucket.tablename",
		"columns": [
			{
				"name": "column1"
			},
			{
				"name": "column2"
			}
		]
	}
]
```

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/ex-teradata
cd my-component
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
