Default upload folder
=====================

Make it possible to configure the default upload folder for a certain TCA column

**How to use:**

1. Download and install default_upload_folder
2. Create the default folders *(folder need to exists and editor needs to have access to the folder)*
3. Add configuration to pageTs ::

    default_upload_folders {
        # folder can be a combined identifier
        tx_news_domain_model_news = 1:news
        # Or a folder relative to the default upload folder of the user
        tx_news_domain_model_news = news

        # You can set a folder for the whole table of for a specific field of that table
        tx_news_domain_model_news.fal_related_files = news_downloads
        tx_news_domain_model_news.fal_media = news_media
    }

**Requirements:**
    TYPO3 7 LTS
