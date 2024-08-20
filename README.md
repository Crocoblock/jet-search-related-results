## Description

JetSearch - Related Results is an enhancement for the JetSearch plugin by Crocoblock. It adds functionality to include related search results in the AJAX search results, providing a more comprehensive and useful search experience for users.

**Please note: This plugin requires JetSearch version 3.5.3 or higher to work correctly**

## Use cases

The description from one of our users:

The real case is: I have a site with Artists and the Paintings. And I want to give the possibility for customers to find the paintings by the Author. So they will type the Author's post title and the search results will display the Author's related paintings.

## Instructions

### Configuration

1. **Ensure JetSearch and JetEngine are Installed**:
    - This plugin requires both JetSearch (minimal suported version is 3.5.3) and JetEngine to be installed and activated. Make sure you have these plugins installed before using JetSearch - Related Results.

2. **Configure Relations in JetEngine**:
    - Define the relations you want to use in JetEngine. Make sure to configure the parent and child objects properly.

3. **Set Relation ID**:
    By default, the plugin does not set a relation ID. You need to specify relation IDs for the plugin to work correctly. Add the following code to your theme or a custom plugin, replacing `YOUR_RELATION_IDS` with your actual relation IDs:
    ```php
    add_filter( 'jet-search/ajax-search/relation_id', function( $rel_id, $query_data, $additional_sources ) {
        return array( 'YOUR_RELATION_ID1', 'YOUR_RELATION_ID2', 'YOUR_RELATION_ID3' );
    } );
    ```

