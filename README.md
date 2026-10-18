# Kirby Plugin: bookblock

This plugin displays book information from Open Library by ISBN. Edition information is combined with available information from the associated work.

## Git submodule

```
git submodule add https://github.com/mirthe/kirby_bookblock site/plugins/bookblock
```

## Usage

No API key is required.

Open Library asks applications to identify themselves with a `User-Agent` that includes a contact e-mail. The plugin uses the Kirby site title and the e-mail configured in `site/config/config.php`:

```php
return [
    'openlibrary.email' => 'you@example.com',
];
```

The block displays the available title, authors, publication date, page count, description, subjects, series and cover. The available fields depend on the edition and work records in Open Library. Page count is also derived from the edition's pagination when a numeric page count is not provided.

## Example 

Placed for example with 

    (bookblock: isbn: 0593135202)

<img src="https://github.com/mirthe/kirby_bookblock/blob/edbc29b1719a587816c63f1e78081c489561b769/example.png" alt="Example of usage">

## Example CSS

See https://css-tricks.com/how-to-make-a-media-query-less-card-component/

## Todo

- Offer as an official Kirby plugin
- Add sample SCSS to this readme
- Cleanup code
- Error handling
- Lots..
