
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
