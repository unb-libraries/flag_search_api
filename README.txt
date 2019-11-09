
-- SUMMARY --

Flag Search API module provides flag indexing for Search API.

Use this module to index flags (flagged content) using Search API module.
Once indexed, the flags can be used elsewhere, e.g. in Views.

It also has support for reindexing flagged entity on flagging action

-- REQUIREMENTS --

Search API
Flag

-- USAGE --

You need to enable flags to be indexed in Search Api index.
There is a processor where you check which flag you'll add into index on content type.
After indexing is performed you can add this field into a view same like any other.
