(function ($) {
    $.fn.sismaEditor = function () {

        var parameters = {
            "elements": {
                "div": {
                    "attributes": {
                        "class": "editor"
                    },
                    "elements": {
                        "div": [{
                                "attributes": {
                                    "class": "editor-toolbar"
                                },
                                "elements": {
                                    "div": [{
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": [{
                                                    "span": [{
                                                            "attributes": {
                                                                "class": "editor-toolbar-menu",
                                                                "title": "Choice Font",
                                                            },
                                                            "elements": [{
                                                                    "i": {
                                                                        "attributes": {
                                                                            "class": "fa fa-font"
                                                                        }

                                                                    },
                                                                    "a": [{
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontName",
                                                                                "data-value": "Times New Roman",
                                                                                "title": "Times New Roman",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-family: Times New Roman;"
                                                                                            },
                                                                                            "content": "Times New Roman"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }, {
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontName",
                                                                                "data-value": "Arial",
                                                                                "title": "Arial",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-family: Arial;"
                                                                                            },
                                                                                            "content": "Arial"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }, {
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontName",
                                                                                "data-value": "Verdana",
                                                                                "title": "Verdana",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-family: Verdana;"
                                                                                            },
                                                                                            "content": "Verdana"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }
                                                                    ]
                                                                }
                                                            ]
                                                        }, {
                                                            "attributes": {
                                                                "class": "editor-toolbar-menu",
                                                                "title": "Font Size",
                                                            },
                                                            "elements": [{
                                                                    "i": {
                                                                        "attributes": {
                                                                            "class": "fa fa-font"
                                                                        }

                                                                    },
                                                                    "a": [{
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontSize",
                                                                                "data-value": "10px",
                                                                                "title": "10px",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-size: 10px;"
                                                                                            },
                                                                                            "content": "10px"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }, {
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontSize",
                                                                                "data-value": "12px",
                                                                                "title": "12px",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-size: 12px;"
                                                                                            },
                                                                                            "content": "12px"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }, {
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontSize",
                                                                                "data-value": "16px",
                                                                                "title": "16px",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-size: 16px;"
                                                                                            },
                                                                                            "content": "16px"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }, {
                                                                            "attributes": {
                                                                                "href": "#",
                                                                                "class": "editor-toolbar-button",
                                                                                "data-action": "fontSize",
                                                                                "data-value": "20px",
                                                                                "title": "20px",
                                                                            },
                                                                            "elements": {
                                                                                "svg": {
                                                                                    "attributes": {},
                                                                                    "elements": {
                                                                                        "text": {
                                                                                            "attributes": {
                                                                                                style: "font-size: 20px;"
                                                                                            },
                                                                                            "content": "20px"
                                                                                        }
                                                                                    }

                                                                                }
                                                                            }
                                                                        }
                                                                    ]
                                                                }
                                                            ]
                                                        }
                                                    ]
                                                }, {
                                                    "a": [{
                                                            "attributes": {
                                                                "href": "#",
                                                                "class": "editor-toolbar-button",
                                                                "data-action": "superscript",
                                                                "title": "Superscript"
                                                            },
                                                            "elements": {
                                                                "i": {
                                                                    "attributes": {
                                                                        "class": "fas fa-superscript"
                                                                    }

                                                                }
                                                            }
                                                        },
                                                        {
                                                            "attributes": {
                                                                "href": "#",
                                                                "class": "editor-toolbar-button",
                                                                "data-action": "subscript",
                                                                "title": "Subscript"
                                                            },
                                                            "elements": {
                                                                "i": {
                                                                    "attributes": {
                                                                        "class": "fas fa-subscript"
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    ]
                                                }
                                            ]
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": [{
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "bold",
                                                            "title": "Bold"
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-bold"
                                                                }

                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "italic",
                                                            "title": "Italic"
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-italic"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "underline",
                                                            "title": "Underline"
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-underline"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "strikeThrough",
                                                            "title": "Strike through"
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-strikethrough"
                                                                }
                                                            }
                                                        }
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": [{
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "justifyLeft",
                                                            "title": "Justify left",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-align-left"
                                                                }

                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "justifyCenter",
                                                            "title": "Justify center",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-align-center"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "justifyRight",
                                                            "title": "Justify right",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-align-right"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "formatBlock",
                                                            "title": "Justify block",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-align-justify"
                                                                }
                                                            }
                                                        }
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "span": [{
                                                        "attributes": {
                                                            "class": "editor-toolbar-menu",
                                                        },
                                                        "elements": [{
                                                                "i": {
                                                                    "attributes": {
                                                                        "class": "fa fa-tint"
                                                                    }

                                                                },
                                                                "a": [{
                                                                        "attributes": {
                                                                            "href": "#",
                                                                            "class": "editor-toolbar-button",
                                                                            "data-action": "hiliteColor",
                                                                            "data-value": "red",
                                                                            "title": "Justify left",
                                                                        },
                                                                        "elements": {
                                                                            "i": {
                                                                                "attributes": {
                                                                                    "class": ""
                                                                                }

                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        "attributes": {
                                                                            "href": "#",
                                                                            "class": "editor-toolbar-button",
                                                                            "data-action": "hiliteColor",
                                                                            "data-value": "blue",
                                                                            "title": "Justify center",
                                                                        },
                                                                        "elements": {
                                                                            "i": {
                                                                                "attributes": {
                                                                                    "class": ""
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                ]
                                                            }
                                                        ]
                                                    },
                                                    {
                                                        "attributes": {
                                                            "class": "editor-toolbar-menu",
                                                        },
                                                        "elements": [{
                                                                "i": {
                                                                    "attributes": {
                                                                        "class": "fa fa-tint"
                                                                    }
                                                                },
                                                                "a": [{
                                                                        "attributes": {
                                                                            "href": "#",
                                                                            "class": "editor-toolbar-button",
                                                                            "data-action": "foreColor",
                                                                            "data-value": "red",
                                                                            "title": "Red",
                                                                        },
                                                                        "elements": {
                                                                            "i": {
                                                                                "attributes": {
                                                                                    "class": ""
                                                                                }

                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        "attributes": {
                                                                            "href": "#",
                                                                            "class": "editor-toolbar-button",
                                                                            "data-action": "foreColor",
                                                                            "data-value": "blue",
                                                                            "title": "Blue",
                                                                        },
                                                                        "elements": {
                                                                            "i": {
                                                                                "attributes": {
                                                                                    "class": ""
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": [{
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "insertOrderedList",
                                                            "title": "Insert ordered list",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-list-ol"
                                                                }

                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "insertUnorderedList",
                                                            "title": "Insert unordered list",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-list-ul"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "outdent",
                                                            "title": "Outdent",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-outdent"
                                                                }
                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "indent",
                                                            "title": "Indent",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-indent"
                                                                }
                                                            }
                                                        }
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": {
                                                    "attributes": {
                                                        "href": "#",
                                                        "class": "editor-toolbar-button",
                                                        "data-action": "insertHorizontalRule",
                                                        "title": "Insert horizontal rule",
                                                    },
                                                    "elements": {
                                                        "i": {
                                                            "attributes": {
                                                                "class": "far fa-window-minimize"
                                                            }

                                                        }
                                                    }
                                                },
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": [{
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "undo",
                                                            "title": "Undo",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-undo"
                                                                }

                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "removeFormat",
                                                            "title": "Remove format",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-eraser"
                                                                }
                                                            }
                                                        }
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": [{
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "createLink",
                                                            "title": "Insert Link",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-link"
                                                                }

                                                            }
                                                        }
                                                    },
                                                    {
                                                        "attributes": {
                                                            "href": "#",
                                                            "class": "editor-toolbar-button",
                                                            "data-action": "unlink",
                                                            "title": "Unlink",
                                                        },
                                                        "elements": {
                                                            "i": {
                                                                "attributes": {
                                                                    "class": "fa fa-chain-broken"
                                                                }
                                                            }
                                                        }
                                                    }
                                                ]
                                            }
                                        }, {
                                            "attributes": {
                                                "class": "editor-toolbar-box"
                                            },
                                            "elements": {
                                                "a": {
                                                    "attributes": {
                                                        "href": "#",
                                                        "class": "editor-toolbar-button",
                                                        "data-action": "code",
                                                        "data-button": "switch",
                                                        "title": "Show HTML-Code",
                                                    },
                                                    "elements": {
                                                        "i": {
                                                            "attributes": {
                                                                "class": "fa fa-code"
                                                            }

                                                        }
                                                    }
                                                },
                                            }
                                        }
                                    ]
                                }
                            },
                            {
                                "attributes": {
                                    "class": "editor-content-area"
                                },
                                "elements": [{
                                        "div": {
                                            "attributes": {
                                                "class": "visuell-view",
                                                "contenteditable": "true"
                                            }

                                        },
                                        "textarea": {
                                            "attributes": {
                                                "class": "html-view",
                                            }

                                        }
                                    }
                                ]
                            }
                        ]
                    }
                }
            }
        };

        function createEditor(elements, elementType = null) {
            let element = $('<div></div>');
            $.each(elements, function (key, value) {
                if ($.isNumeric(key) === false) {
                    elementType = key;
                }
                if (value.attributes !== undefined) {
                    let subElement = ($(document.createElement(elementType)));
                    subElement.attr(value.attributes);
                    if (value.content !== undefined) {
                        subElement.append(value.content);
                    }
                    if (value.elements !== undefined) {
                        subElement.append(createEditor(value.elements, elementType));
                    }
                    element.append(subElement);
                } else {
                    element.append(createEditor(value, elementType));
                }
            });
            return element.children();
        }

        var originalTextarea = this;
        var contentOriginaTextarea = originalTextarea.text();
        originalTextarea.hide();
        var editor = createEditor(parameters.elements);
        var htmlButtons = editor.find(".editor-toolbar-button");
        var visuellView = editor.find(".visuell-view");
        var htmlView = editor.find(".html-view");
        visuellView.html(contentOriginaTextarea);
        htmlView.hide();
        $(this).after(editor);

        htmlButtons.on('click', function () {
            let value;
            if ($(this).data('value') !== undefined) {
                value = $(this).data('value');
            } else {
                value = null;
            }
            switch ($(this).data('action')) {
                case 'code':
                    execCodeAction($(this));
                    break;
                case 'createLink':
                    execLinkAction();
                    break;
                default:
                    execDefaultAction($(this).data('action'), value);
            }
            if ($(this).data('button') === 'switch') {
                if ($(this).hasClass('active')) {
                    $(this).removeClass('active');
                } else {
                    $(this).addClass('active');
                }
            }
            if (visuellView.is(":visible")) {
                originalTextarea.val(visuellView.html());
            } else if (htmlView.is(":visible")) {
                originalTextarea.val(htmlView.val());
            }
        });

        function execCodeAction(button) {
            if (button.hasClass('active')) {
                visuellView.html(htmlView.val());
                htmlView.hide();
                visuellView.show();
            } else {
                htmlView.val(visuellView.html());
                visuellView.hide();
                htmlView.show();
            }
        }

        function execLinkAction() {
            let linkValue = prompt('Link (e.g. https://webdeasy.de/)');
            document.execCommand('createLink', false, linkValue);
            return true;
        }

        function execDefaultAction(action, value) {
            document.execCommand("styleWithCSS", null, true);
            document.execCommand(action, false, value);
            return true;
        }

        $(visuellView).on("blur keyup paste", function () {
            originalTextarea.val($(this).html());
            return $(this);
        });

        $(htmlView).on("blur keyup paste", function () {
            originalTextarea.val($(this).val());
            return $(this);
        });
    };
})(jQuery);
        