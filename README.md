Default upload folder
=====================

Make it possible to configure the default upload folder for a certain TCA column

**How to use:**

1. Download form TER or require (`composer require beechit/default-upload-folder`) extension default_upload_folder
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
    }
```

**FAQ**

_What happens when the user does not have access to the upload folder?_
> The "Select & upload" button is not available

_How do the fallbacks work?_
> 1. First it will check if there is a default upload folder for the table & field combination.
> 2. Then it will check if there is a default upload folder for the table.
> 3. At last it will check if there is configuration for `defaultForAllTables`

_Are folders automatically created?_
> Yes, but only if path set with combined identifiers like 1:myNewsPicturesFolder

**Requirements:**

> TYPO3 10 LTS
