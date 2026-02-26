# Plugin Update Check

## Config

Plugin is configured via Butlerfile. Add key "update_check" to JSON. Example Config:  

````json
"update_check" : {
    "run_in_env": "butler",
    "ticket_frequency": "weekly",
    "ignore": {
        "plugins": [],
        "themes": []
    },
    "GitlabConfig": {
        "url": "https://gitlab.palasthotel.de",
        "ProjectNamespace": "",
        "ProjectName": "",
        "PrivateToken": "",
        "Assignee": "muster.maxfrau",
        "dueDays": "4",
        "Labels": "Test",
        "TicketDescriptionSuffix": ""
    }
}
````

## Plugin Configuration


### ```run_in_env``` (string)
in which server environment/context the checks should be run. Possible values:
- ```butler```
- ```stage```
- ```production```

On freistil servers the value is retrieved from /config/site-config.json. Should be ```stage``` or ```production``` but can be different. Check to make sure. 

### ```ticket_frequency``` (string)
interval between issue creation. Possible values:
- - ```weekly```
  - ```bi-weekly``` (as in _every 2 weeks_)
  - ```monthly```
- if empty, defaults to ```weekly```

### ```ignore``` (object)
ignore updates 
- ```plugins``` - array of strings, e.g. the plugin slug as defined by the plugin; e.g. ```["wordpress-seo", "gutenberg"]```
- ```themes``` - array of strings, the theme slug as defined by the theme

### GitlabConfig
#### ```dueDays``` (integer)
number of days from ticket creation to be set as due date. if due date falls on weekend, the next monday is chosen.
- defaults to value of ```3```