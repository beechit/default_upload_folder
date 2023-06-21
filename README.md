Default upload folder
=====================

Make it possible to configure the default upload folder for a certain TCA column

**How to use:**

1. Download from TER or require (`composer require beechit/default-upload-folder`) extension default_upload_folder
2. Install extension default_upload_folder via the extension manager
3. Create the default folders or the folder is automatically created *(Editors needs access to storage and the folder root)*
4. Add configuration to pageTs

```
    default_upload_folders {
        # folder can be a combined identifier
        tx_news_domain_model_news = 1:news
        # Or a folder relative to the default upload folder of the user
        tx_news_domain_model_news = news

        # You can set a folder for the whole table of for a specific field of that table
        tx_news_domain_model_news.fal_related_files = news_downloads
        tx_news_domain_model_news.fal_media = news_media

        # You can set a fallback for all tables
        defaultForAllTables = 1:myDefaultUploadFolderForThisPartOfTheTree
        
        # You can set a default year/month/day folder within the set default folder
        tx_news_domain_model_news.dateformat = 1
        tx_news_domain_model_news = 1:news/{Y}/{n}
    }
```



**FAQ**

_What happens when the editor does not have access to the upload folder?_
> The "Select & upload files" and "Add media by URL" buttons are not available for the editor

_How do the fallbacks work?_
> 1. First it will check if there is a default upload folder for the table & field combination.
> 2. Then it will check if there is a default upload folder for the table.
> 3. Finally, it will check if there is configuration for `defaultForAllTables`

_Are folders automatically created?_
> Yes, but only if path set with combined identifiers like 1:myNewsPicturesFolder

_How to use the year/month/week/day feature?_
> 1. Make sure the variable `tx_mews_domain_model_news` has the `dateformat` value set to `1`.
> 2. Then (over)write the original variable however you prefer: `tx_news_domain_model_news = 1:news/{Y}/{n}`
> 3. This will translate into: `1:news/2023/06` which in turn creates the directory: `news/2023/06`

_Why does the year/month/week/day feature not use the php strftime function & format?_

> Php 8.1 is going to mark strftime to depricated, and will [fully depricate in php 9.](https://www.php.net/manual/en/function.strftime.php) 
> 
> Currently, there is no proper solution that takes localisation in consideration. Hence, the choice to create a custom interpreter.
> The values used are based on the [date() -> Parameter Values](https://www.w3schools.com/php/func_date_date.asp) format.
> the values currently in use are:
> - Y - A four digit representation of a year
> - y - A two digit representation of a year
> - m - A numeric representation of a month (from 01 to 12)
> - n - A numeric representation of a month, without leading zeros (1 to 12)
> - d - The day of the month (from 01 to 31)
> - j - The day of the month without leading zeros (1 to 31)
> - W - The ISO-8601 week number of year (weeks starting on Monday)
> - w - A numeric representation of the day (0 for Sunday, 6 for Saturday)
> <br/>
> The other values are currently **not** in use.
> <br/>


**Requirements:**

> TYPO3 10 LTS or TYPO3 11 LTS
