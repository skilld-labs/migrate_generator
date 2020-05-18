# migrate_generator
Module to generate migrations based on source CSV files

## Basic idea

Main idea here is to **automatically generate migration config based on source csv**.

------------
## Drush command

`drush migrate_generator:generate_migrations %arg`

Where %arg - absolute path to directory with source .csv files.

Also we have next possible options:
* pattern -
  Pattern for source CSV files. Defaults to '*.csv'.
* delimiter -
  Delimiter for source CSV files. Defaults to ;
* enclosure -
  Enclosure for source CSV files. Defaults to "
* values_delimiter -
  Delimiter for multi-valued fields. Defaults to |
* date_format -
  Date format used in CSV. Defaults to "d-m-Y H:i:s"
* file_source -
  Type of filepath sources, used in CSV.
  Can be "external" (means external URLs to files) or "absolute" (absolute filepath to local files).
  Defaults to "absolute".

This command creates Migration Group named `Migrate generator group` (if not exists) and migration for each source file.

For now, this drush command doesn't update existing migration, so if you need to regenerate it - you'd remove existing migration fisrt.

## CSV file structure and contents

We should use next ways of organizing source files:
* Source CSV files should next filename`{entity_type}-{bundle}.csv` or `{entity_type}.csv`.

  You can see some examples in /example folder of this module, like `node-basic_page.csv`, `taxonomy_term-category.csv`, `user.csv`, etc.

**Main idea here - to identify target Entity type and Bundle for source file.**

There are some rules for structure and content of source csv file:
* Each source file should have first column filled with Ids (it will be used as unique identifier for source row)
* All other columns should be named with exact field's machine name.
* For boolean fields you can use "1/0" or "true/false" values.
* For list fields, we should have key values in source csv, not labels.
* Fields with multiple values should have strict delimiter. `|` for example.

  This delimiter can be specified by **values_delimiter** option in drush command.
* Date fields should have same date format everywhere in sources.

  This date format can be specified by **date_format** option in drush command.

## Complex fields with multiple properties.

For complex fields, like Formatter text, Link, Datetime Range, Address, Price, we could have several properties in source file.
In this case, you can use `/` separator in column name for these cases.

For example, `body/value`, `body/format`, `price/number`, `price/currency_code`, etc.

Example of supported complex field types:
  - **formatted text** (we have format and value properties)
  - **link** (we could have uri and title properties)
  - **datetime_range** (we could have value and end_value properties)
  - **address**
  - **price**. (we could have number and currency_code properties)

Any other complex field type also could work this way, but it is not guaranteed.

## File and Image fields.

For file migration, we could have 2 possible cases:
  - we have absolute filepath in csv
  - we have URLs in csv

This can be specified by **file_source** option in drush command.

* For `absolute` case, you'd have absolute filepath to files in csv.

  These files will be copied to Drupal filesystem into folder, named by field name.

* For `external` case, you'd have URLs to external files in csv.

  These files will be downloaded to Drupal filesystem into folder, named by field name.

## Reference fields support

References is most complicated part here, as we should have migration dependencies for them

**For all reference fields, we should have Id from corresponding source as field value.**

For example, in `category` column in content source csv, you'd have Id of this term and term source should have row with this Id in its source file.
in node-basic_page.csv:

...| category |...|
---| --- | --- |
...| 12 | ... |

and in taxonomy_term-category.csv :

| id | name |
| --- | --- |
| 12 | test category |

Also we could have some complicated cases, when we have multiple dependencies.
For example:
1. Paragraph field in Basic Page CT could have references to WYSIWYG and Media paragraphs.
so we will have separate sources for these paragraphs `paragraph-media.csv`, `paragraph-wysiwyg.csv`

2. Media paragraph could have references to Image media and Document media.
so we will have separate media sources here too `media-document.csv`, `media-image.csv`

