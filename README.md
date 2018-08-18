# Teradata Extractor

[![Build Status](https://travis-ci.com/keboola/ex-teradata.svg?branch=master)](https://travis-ci.com/keboola/ex-teradata)

Keboola Connection Extractor for [Teradata](https://www.teradata.com/)

# Usage

The following parameters are required:

- `host` - IP address or Hostname for Teradata server
- `username` - User with correct access rights
- `#password` - Password for given User
- `database` - Database name

At least one table must be must be specified.

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
