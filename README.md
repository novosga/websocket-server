# Websocket server

[Novo SGA](http://novosga.org) websocket events server writted in PHP compatible since v2.0.

## Events

### Client-to-server

**register client**

Panel Client register.

*(No event data)*


**register user** [user-only]

User Client register

Event data for `User`:

```json
{
    "unity": integer,
    "secret": string
}
```

Event data for `Panel`:

```json
{
    "unity": integer,
    "services": array
}
```


**new ticket**

User Client on triagem or redirecting on attendance

Event data:

```json
{
    "unity": integer
}
```

**change ticket** [user-only]

User Client on monitor emitted when cancel or transfer ticket

Event data:

```json
{
    "unity": integer
}
```


**call ticket** [user-only]

User Client on attendance

Event data:

```json
{
    "unity": integer,
    "service": integer,
    "hash": string
}
```


**client update**

Client info update

Event data for `User`:

```json
{
    "unity": int
}
```

Event data for `Panel`:

```json
{
    "unity": integer,
    "services": array
}
```

### Server-to-client

**register ok**

*(No event data)*


**update queue** [user-only]

Emmited when: `new ticket`, `change ticket`, `call ticket`.

*(No event data)*


**call ticket** [panel-only]

Emmited when: `call ticket`.

Event data for Panel:

```json
{
    "unity": integer,
    "service": integer,
    hash: string
}
```