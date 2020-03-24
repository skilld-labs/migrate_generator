# migrate_generator
Module to generate migrations based on source CSV files

## Basic idea

Main idea here is to **automatically generate migration config based on source csv**.

We could use next ways of organizing source files:
* Have next structure of folders for CSV files (like we have for default_content):
```
-content/
--basic_page/
--article/
-taxonomy_term/
--category
--brands
-user/
...
```
* Having the same, but in source filename, like described in https://github.com/davidferlay/skilld-docker-container/tree/replacedefaultcontentbymigrate/web/modules/custom/generic_content_importer#details, for example `node-basic_page.csv`, `taxonomy_term-category.csv`. etc

*We could have language prefix here also, but I'd say that for 1st iteration, we shouldn't care about translation*

**Main idea here - to have strict rules to identify target Entity type and Bundle for source file.**

------------
## Fields support

In each source file, we should have Id column required (as unique identifier for row) and all other columns with exact field machine_names. 

Need to take care about next things:
* Language field should have language code in source csv, not labels
* Fields with multiple values should have strict delimiter. `|` for example.
* For list fields, we should have keys in source csv, not labels
* Date fields should have strict date format everywhere.
* Sometimes, field consists of several values, need to support that too. We can use `/` separator in column name for these cases.
Example of field types to support:
  - **formatted text** (we have format and value)
  - **link**
  - **datetime_range**
  - **address**
  - **price**. 
 
* For file migration, we could have 2 possible cases:
  - we have absolute filepath in csv -> then we use file_copy process plugin
  - we have URLs in csv -> we use download plugin

   and we could use our plugin https://www.drupal.org/project/migrate_plus/issues/3113394 here too

**TODO:** What other specific field types should we support?

------------
## Reference fields support

**References is most complicated part here, as we should have migration dependencies for them**

We could handle it next way:
1. We know, what sources do we have.
2. We know field machine name from csv header, so from field definition we could know if it is reference field and what entity and bundle is the target

So, if we also have a source for this entity and bundle => then **we have migration dependency here**

**For all reference fields, we should have Id from corresponding source in field's column.**

For example, if you have related term for content, in `category` column in content source csv, you'd have id of this term and term source should have row with this Id in its source file:
in node csv :

| category |
| --- |
| 12 |

and in voc category.csv :

| id | name |
| --- | --- |
| 12 | test category |


This makes structure to be pretty strict and it could have some "useless" sources. 

For example, for Media references, content is referencing Media entity and then media entity is referencing file entity => so we'd have separate sources for Content, Media and File.

Multiple csv files can be hard to maintain, but this is the price for universal tool.

We cannot use `entity_generate` plugin here, as we need universal source structure that should fit any projects:
* Related entity could have relation to some other entity. This case cannot be handled with entity_generate atm 
* You cannot use process plugins inside entity_generate
* Improving it would make it overly complex (there are migrations itself for creating complex objects)
* We would loose ability to rollback 

------------
#### Example of source structure in diagram:

![image](https://s3.amazonaws.com/awesomescreenshot/upload//38107/286deb84-d59d-4a7e-5d21-4aa42a704e96.png?AWSAccessKeyId=AKIAJSCJQ2NM3XLFPVKA&Expires=1585075035&Signature=ZzcFSbpcQ1DogmNkEnGSW05J2M0%3D)

Here you can see some complicated cases, when we have multiple dependencies:
1. Paragraph field in Basic Page CT could have references to WYSIWYG paragraph and Media paragraph
2. Media paragraph could have references to Image media and Document media

So will have to use `migration_lookup` process plugin on several migrations

PS As you can see even for this pretty simply content structure, we will have to manage too many source files. 

I have some doubts if it is really worth it, cause create content from drupal UI and export with default_content - will be much easier here.
