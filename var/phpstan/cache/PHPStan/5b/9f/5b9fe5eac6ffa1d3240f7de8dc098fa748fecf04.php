<?php declare(strict_types = 1);

// odsl-/var/www/html/custom/plugins/WbmProductTypeFilter/src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Elasticsearch/Product/ElasticsearchProductDefinitionDecorator.php' => 
    array (
      0 => '0763c7cae7f7b9f798a7774707fd19827983e1c1',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\elasticsearch\\product\\elasticsearchproductdefinitiondecorator',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\elasticsearch\\product\\__construct',
        1 => 'wbm\\producttypefilter\\elasticsearch\\product\\getentitydefinition',
        2 => 'wbm\\producttypefilter\\elasticsearch\\product\\getmapping',
        3 => 'wbm\\producttypefilter\\elasticsearch\\product\\fetch',
        4 => 'wbm\\producttypefilter\\elasticsearch\\product\\buildtermquery',
        5 => 'wbm\\producttypefilter\\elasticsearch\\product\\normalizetobyteslist',
        6 => 'wbm\\producttypefilter\\elasticsearch\\product\\normalizetohex',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/WbmProductTypeFilter.php' => 
    array (
      0 => 'c1a41dd045a426336c739c1959de96c8a61d9347',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\wbmproducttypefilter',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\install',
        1 => 'wbm\\producttypefilter\\postinstall',
        2 => 'wbm\\producttypefilter\\uninstall',
        3 => 'wbm\\producttypefilter\\inserttestproducttypes',
        4 => 'wbm\\producttypefilter\\getconnection',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Migration/Migration1769025113CreateWbmProductTypeExtensionTable.php' => 
    array (
      0 => '7e4274ecca813e118f9d51b5ec37bc62c34b5275',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\migration\\migration1769025113createwbmproducttypeextensiontable',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\migration\\getcreationtimestamp',
        1 => 'wbm\\producttypefilter\\migration\\update',
        2 => 'wbm\\producttypefilter\\migration\\updatedestructive',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Subscriber/ProductTypeCustomSearchKeywordsSubscriber.php' => 
    array (
      0 => '3bb0248badca3ab2f3d95ea3f56a83e51125c73b',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\producttypecustomsearchkeywordssubscriber',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\__construct',
        1 => 'wbm\\producttypefilter\\subscriber\\getsubscribedevents',
        2 => 'wbm\\producttypefilter\\subscriber\\onproductwritten',
        3 => 'wbm\\producttypefilter\\subscriber\\normalizetobyteslist',
        4 => 'wbm\\producttypefilter\\subscriber\\decodekeywordarray',
        5 => 'wbm\\producttypefilter\\subscriber\\containscaseinsensitive',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Subscriber/ProductTypeListingFilterSubscriber.php' => 
    array (
      0 => 'd70558ae96a21b7a77bdbf2ee4c8c09ed0f81d05',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\producttypelistingfiltersubscriber',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\getsubscribedevents',
        1 => 'wbm\\producttypefilter\\subscriber\\addfilter',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Subscriber/PluginLifecycleSubscriber.php' => 
    array (
      0 => '328f59572e36fbdbf9f865500402a73c4e2f6ce5',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\pluginlifecyclesubscriber',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\subscriber\\__construct',
        1 => 'wbm\\producttypefilter\\subscriber\\getsubscribedevents',
        2 => 'wbm\\producttypefilter\\subscriber\\reindex',
        3 => 'wbm\\producttypefilter\\subscriber\\runcommand',
        4 => 'wbm\\producttypefilter\\subscriber\\getapplication',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Command/ProductTypeSyncCommand.php' => 
    array (
      0 => '805ebe2af0f0b55aaa32ed67447929f65a5dc42a',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\command\\producttypesynccommand',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\command\\__construct',
        1 => 'wbm\\producttypefilter\\command\\configure',
        2 => 'wbm\\producttypefilter\\command\\execute',
        3 => 'wbm\\producttypefilter\\command\\flushupserts',
        4 => 'wbm\\producttypefilter\\command\\parsepositiveint',
        5 => 'wbm\\producttypefilter\\command\\decodekeywordarray',
        6 => 'wbm\\producttypefilter\\command\\containscaseinsensitive',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Core/Content/ProductTypeExtension/SalesChannelProductExtension.php' => 
    array (
      0 => '1d4cee12638aa9582ce22499749fb93773f286ce',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\saleschannelproductextension',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\extendfields',
        1 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getentityname',
        2 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getdefinitionclass',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Core/Content/ProductTypeExtension/ProductTypeExtensionCollection.php' => 
    array (
      0 => '99b77d7e3c657f90b3c5a047cf1872e1988774a8',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\producttypeextensioncollection',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getexpectedclass',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Core/Content/ProductTypeExtension/ProductExtension.php' => 
    array (
      0 => '945bb1dedec75d22f14ea5d3f9319412a4707959',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\productextension',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\extendfields',
        1 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getdefinitionclass',
        2 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getentityname',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Core/Content/ProductTypeExtension/ProductTypeExtensionEntity.php' => 
    array (
      0 => 'dcd9fe8c69403ef47a03a2cc2b812c838c4413be',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\producttypeextensionentity',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getproductid',
        1 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\setproductid',
        2 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getproductversionid',
        3 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\setproductversionid',
        4 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getproductidfromapi',
        5 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\setproductidfromapi',
        6 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getproducttype',
        7 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\setproducttype',
      ),
      3 => 
      array (
      ),
    ),
    '/var/www/html/custom/plugins/WbmProductTypeFilter/src/Core/Content/ProductTypeExtension/ProductTypeExtensionDefinition.php' => 
    array (
      0 => 'd447242fe2e83ebf930b91eb925735b09c974dd0',
      1 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\producttypeextensiondefinition',
      ),
      2 => 
      array (
        0 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getentityname',
        1 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getentityclass',
        2 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getcollectionclass',
        3 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\getdefaults',
        4 => 'wbm\\producttypefilter\\core\\content\\producttypeextension\\definefields',
      ),
      3 => 
      array (
      ),
    ),
  ),
));