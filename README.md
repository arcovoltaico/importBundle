XML IMPORTER DOC
©2016 Jordi Alhambra (@arcovoltaico)


#The goal
When you got an XML containing an array of records that you need to normalize/map & store to your database
and you want the job done just by writing a config file. The importer can even copy images or docs from the XML urls to your DB & server.


#How to install the bundle

1. Composer & Packagist support to be announced, meanwhile you can download the zip and locate it inside your Symfony src folder.
2. Add this line to your Symfony AppKernel:

         new ArcoVoltaico\ImportBundle\ArcoVoltaicoImportBundle(),
3. On your app/config.yml include
    
        imports:
            - { resource: security.yml }
            - { resource: config_importer.yml }

4. Create app/config_importer.yml with:
            
        arco_voltaico_import:
    
            namespace: AppBundle\Entity
            bundle: AppBundle
            entities: 

5. Sync your db schema in order to create the import entity

#Setting the importer

Example:

    entities: 
            #from parent to related
            product:
                    clear: true
                    import:
                            name:
                                xml: CatNumber
                                
                            type:
                                xml: Type
                                class: producttype
                                map:
                                    1: 2
                                    2: 1
                                    3: 2
                                    4: 2
                                    5: 1 
                                    6: 3
                                    7: 3
                                    8: 4
                                    10: 1
                                    11: 1 
                                    8B49282169C34E89B6526E36F227D743: 3
                                    
                            liveable:
                                xml: Type
                                activate: [1,2,5,11]
                                
                                
                            floor:
                                xml: Address.Floor

                                    
            offer:
                    clear: true
                    parent: product
                    multiple: 
                        - Mode.Sale
                        - Mode.Rent
                    import:
                            price:
                                    xml: Amount
                                    nullable: false
                                    
                            type:        
                                    class: offertype
                                    mirror:                       
                                                - 1
                                                - 2
                            info:
                                    xml: <<Description
                
            image:
                    clear: false
                    parent: product
                    multiple: Photos.Photo
                    upload: true
                    path: '/uploads/images/'
                    import:
                            position: 
                                    xml: _index
                            name:
                                    xml: Document
                                        
                            info:
                                    xml: Description
                                        
                            path: 
                                    xml: Document
                                         
                            url:    
                                    xml: Document
                                    




In this example we'd copy the XML content to 3 different entities: product, offer and image.
Each entity includes **general attributes**:

- **clear**: if true, the table will be truncated before syncing
- **parent**: the name of the parent entity
- **multiple**: if there is N records( offers, images...)  per parent record (product in our example)
    - if defined by an array then we have a limited children records, and they will be 'mirrored' (see below)
    - if defined by a single xml root, then we are expecting an unlimited children for each parent record. ie: ilimited images per product 
- **upload**:true if we want to copy the file  to our server. The file will be retrieved from the url providen on the url entity attribute. 
    - In case the url is relative you'd need to add a method to your entity (Image on this case)
                     
         function getUrl() {
            return 'http://domain/anyroute'.$this->url;
        }
- **path**: if you activated upload, then you need to locate where the files will be copied


- **import**: here we include all the attributes we want to import.


###Different importing modes

On each attribute you always need to set a **xml** including the route to the resource on the XML. Keep in mind that we can define a ../ style route, just by adding a < before the route.
If we are defining an unlimited multiple children entity, we'd like to use **_index** as the value of this xml param, in case we want the index.

Sometimes we don't want to create a record unless an attribute is defined in the xml. If so, we'll add the param
**nullable:**true


Sometimes we want to deduce the value of an atribute depending on the multiple value. See the offer example, where we assign the oofertype with id 1 when we are importing from Modalidades.Venta.


When we have a boolean attribute is possible to **activate** it only on some cases (i.e.: liveable will be true if Tipo is 1 or 2...)


If we need to 'translate' the values from the XML to your record, then we can use **map**, so the left value (the one to be found in the xml) will be converted to the right value.


#Additional data modificattion

Maybe we'll need to assign value to some entity attribute that are not available on the XML, chances are that :

- this value depends on another attribute from teh same entity:
    - So now you can fix this by tweaking the entity setters and/or constructor method.
- it's more complex...:
    -  so you'll need to assign the attributes from an Event Listener.


#Importing


- go to yourdomain/en/import/new and upload your xml, so the import will start
- you can reimport an xml by executing yourdomain/en/import/sync/ID_NUMBER


#Restrictions/Conventions


- We are only able to extract data from inside every XML node or from it's id attribute.

- We can manage only one master entity, and it mut be followed by its children.

- The fields, whose value will be part of other field, will , must be earlier in the import.

- If we use some kind of quoted UID on your mappings in the config file, we'll remove the quotes.


#Future improvements

- Differ image or doc r/w in order to reduce the overall script execution , now we are using this on the controller:

        ini_set('memory_limit','1536M'); // 1.5 GB
        ini_set('max_execution_time', 18000); // 5 hours

- Limit to copy only last N days / updated images
- Better error catching, stop/continue import mode, error log
- Better doctrine flush strategy? (maybe preflush y and then a doctrine listener should make the flush)