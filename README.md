# Teradata Extractor

[![Build Status](https://travis-ci.com/keboola/ex-teradata.svg?branch=master)](https://travis-ci.com/keboola/ex-teradata)
[![Code Climate](https://codeclimate.com/github/keboola/ex-teradata/badges/gpa.svg)](https://codeclimate.com/github/keboola/ex-teradata)
[![Test Coverage](https://codeclimate.com/github/keboola/ex-teradata/badges/coverage.svg)](https://codeclimate.com/github/keboola/ex-teradata/coverage)

Keboola Connection Extractor for [Teradata](https://www.teradata.com/)

# Configuration

## Options

The configuration requires a `db` node with the following properties: 

- `host` - string (required): IP address or Hostname of Teradata server
- `user` - string (required): User with correct access rights
- `#password` - string (required): Password for given User
- `database` - string (required): Database name

There are 2 possible types of table extraction.  
1. A table defined by `schema` and `tableName`, this option can also include a columns list.
2. A `query` which is the SQL SELECT statement to be executed to produce the result table.

The extraction has the following configuration options:

- `query`: string (optional, but required if `table` not present)
- `table`: array (optional, but required if `query` not present)
  - `schema`: string
  - `tableName`: string
- `columns`: array of strings (only for `table` type configurations)
- `outputTable`: string (required)
- `incremental`: boolean (optional)
- `primaryKey`: array of strings (optional)

## Example
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
        "query": "SELECT COUNT(*) c, column1 FROM tddatabase.tablename GROUP BY column1",
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

# Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/ex-teradata
cd ex-teradata

```

Generate `.env` file with AWS credentials for drivers download:

```
docker run --rm -it \
--volume $HOME/.aws/:/root/.aws/ --volume $(pwd):/code --entrypoint '' \
quay.io/keboola/aws-cli:latest sh -c "aws sts get-session-token | jq --raw-output '\"AWS_ACCESS_KEY_ID=\" + .Credentials.AccessKeyId + \"\nAWS_SECRET_ACCESS_KEY=\" + .Credentials.SecretAccessKey + \"\nAWS_SESSION_TOKEN=\" + .Credentials.SessionToken' > /code/.env"
```

Append to `.env` file:
```
TERADATA_HOST=100.200.30.40
TERADATA_USERNAME=user
TERADATA_PASSWORD=password
TERADATA_DATABASE=database_name

```

Build the image:
```
docker-compose build dev
docker-compose run --rm dev composer install --no-scripts
```

## Tools

- Tests: `docker-compose run --rm dev composer tests`
  - Unit tests: `docker-compose run --rm dev composer tests-phpunit`
  - Datadir tests: `docker-compose run --rm dev composer tests-datadir`
- Code sniffer: `docker-compose run --rm dev composer phpcs`
- Static analysis: `docker-compose run --rm dev composer phpstan`

 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
