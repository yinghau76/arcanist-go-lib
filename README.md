arcanist-go-lib
===================

Set of Arcanist classes for working with applications written in Go programming language. Currently 'golint' and 'go vet' are supported.

## General usage

Typically, you will want to clone this repository next to your project and your
own Arcanist library, then load it in your `.arcconfig`:

```
...
"load": [
  "arcanist-go-lib",
  "your-library"
],
...
```

You can configure arc to use these linters in `.arclint`:

```json
{
  "linters": {
    "golint" : {
      "type" : "golint",
      "include" : "(\\.go$)"
    },
    "govet" : {
      "type" : "govet",
      "include" : "(\\.go$)"
    }
  }
}
```

## License

Copyright 2014 Patrick Tsai

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
