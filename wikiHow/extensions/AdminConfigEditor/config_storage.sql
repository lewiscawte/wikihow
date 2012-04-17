CREATE TABLE /*_*/config_storage (
	cs_key VARCHAR(64) NOT NULL PRIMARY KEY,
	cs_config LONGTEXT NOT NULL
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/config_storage SET cs_key='wikiphoto-article-exclude-list', cs_config='';