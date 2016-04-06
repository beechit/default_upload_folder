FAL SecureDownLoad
======================

This extension (fal_securedownload) aims to be a general solution to secure your assets.

When you storage is marked as non-public all links to files from that storage are replaced (also for processed files).

The access to assets can be set on folder/file bases by setting access to fe_groups in the file module.

**How to use:**

1. Download and install fal_securedownload
2. Create the default folders _(folder need to exists and editor needs to have access to the folder)_
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
