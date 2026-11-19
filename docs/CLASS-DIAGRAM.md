# Initial class diagram

```mermaid
classDiagram
  User "1" --> "*" Entry : author
  Entry "0..1" --> "*" Entry : parent
  User "1" --> "*" SavedItem
  Entry "1" --> "*" SavedItem
  SavedItem : user_id
  SavedItem : entry_id
  User : id
  User : name
  User : email
  User : password
  User : role
  Entry : id
  Entry : user_id
  Entry : parent_id
  Entry : kind
  Entry : title
  Entry : body
  Entry : photo
  Entry : status
  Entry : details
```

Entry kinds share an author, lifecycle status, and optional parent. The migration enforces parent and author foreign keys. The controller enforces allowed parent kinds and permissions. Examples include a pet update under its pet, a review under its shop, and a sighting under its lost notice. Kind-specific details are stored in the JSON details column.
