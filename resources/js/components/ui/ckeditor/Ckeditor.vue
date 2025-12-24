<template>
    <div>
        <div class="main-container">
            <div class="editor-container editor-container_document-editor editor-container_include-minimap" ref="editorContainerElement">
                <div class="editor-container__menu-bar" ref="editorMenuBarElement"></div>
                <div class="editor-container__toolbar" ref="editorToolbarElement"></div>

                    <div class="editor-container__editor-wrapper">
                        <div class="editor-container__editor">
                            <div ref="editorElement">
                                <ckeditor
                                    v-if="isLayoutReady"
                                    :editor="editor"
                                    :config="config"
                                    @ready="onReady"
                                    @input="onEditorInput"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="editor-container__sidebar editor-container__sidebar_minimap">
                        <div ref="editorMinimapElement"></div>
                    </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
    DecoupledEditor,
    AccessibilityHelp,
    Alignment,
    Autoformat,
    AutoImage,
    AutoLink,
    Autosave,
    BalloonToolbar,
    Bold,
    Code,
    Essentials,
    FindAndReplace,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    Heading,
    HorizontalLine,
    ImageBlock,
    ImageCaption,
    ImageInline,
    ImageInsert,
    ImageInsertViaUrl,
    ImageResize,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    LinkImage,
    List,
    ListProperties,
    Mention,
    Minimap,
    PageBreak,
    Paragraph,
    PasteFromOffice,
    RemoveFormat,
    SelectAll,
    SimpleUploadAdapter,
    SpecialCharacters,
    SpecialCharactersArrows,
    SpecialCharactersCurrency,
    SpecialCharactersEssentials,
    SpecialCharactersLatin,
    SpecialCharactersMathematical,
    SpecialCharactersText,
    Strikethrough,
    Subscript,
    Superscript,
    Table,
    TableCaption,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    TextTransformation,
    Title,
    TodoList,
    Underline,
    Undo,
    FileRepository
} from 'ckeditor5'
import 'ckeditor5/ckeditor5.css'
import { usePage } from '@inertiajs/vue3'

interface Props {
  enableTitle?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  enableTitle: true
})

const emit = defineEmits<{
  ready: [editor: any]
  'update:modelValue': [event: any]
  'editor-ready': [editor: any]
}>()

const page = usePage()

const isLayoutReady = ref(false)
const config = ref<any>(null)
const editor = ref(DecoupledEditor)

const editorContainerElement = ref<HTMLElement | null>(null)
const editorMenuBarElement = ref<HTMLElement | null>(null)
const editorToolbarElement = ref<HTMLElement | null>(null)
const editorElement = ref<HTMLElement | null>(null)
const editorMinimapElement = ref<HTMLElement | null>(null)

onMounted(() => {
  config.value = {
    toolbar: {
      items: [
        'undo',
        'redo',
        '|',
        'heading',
        '|',
        'fontSize',
        'fontFamily',
        'fontColor',
        'fontBackgroundColor',
        '|',
        'bold',
        'italic',
        'underline',
        '|',
        'link',
        'insertImage',
        'insertTable',
        '|',
        'alignment',
        '|',
        'bulletedList',
        'numberedList',
        'todoList',
        'outdent',
        'indent',
        '|',
      ],
      shouldNotGroupWhenFull: false
    },
    plugins: [
      AccessibilityHelp,
      Alignment,
      Autoformat,
      AutoImage,
      AutoLink,
      Autosave,
      BalloonToolbar,
      Bold,
      Code,
      Essentials,
      FindAndReplace,
      FontBackgroundColor,
      FontColor,
      FontFamily,
      FontSize,
      Heading,
      HorizontalLine,
      ImageBlock,
      ImageCaption,
      ImageInline,
      ImageInsert,
      ImageInsertViaUrl,
      ImageResize,
      ImageStyle,
      ImageTextAlternative,
      ImageToolbar,
      ImageUpload,
      Indent,
      IndentBlock,
      Italic,
      Link,
      LinkImage,
      List,
      ListProperties,
      Mention,
      Minimap,
      PageBreak,
      Paragraph,
      PasteFromOffice,
      RemoveFormat,
      SelectAll,
      SimpleUploadAdapter,
      SpecialCharacters,
      SpecialCharactersArrows,
      SpecialCharactersCurrency,
      SpecialCharactersEssentials,
      SpecialCharactersLatin,
      SpecialCharactersMathematical,
      SpecialCharactersText,
      Strikethrough,
      Subscript,
      Superscript,
      Table,
      TableCaption,
      TableCellProperties,
      TableColumnResize,
      TableProperties,
      TableToolbar,
      TextTransformation,
      Title,
      TodoList,
      Underline,
      Undo,
      FileRepository
    ],
    balloonToolbar: ['bold', 'italic', '|', 'link', 'insertImage', '|', 'bulletedList', 'numberedList'],
    fontFamily: {
      supportAllValues: true
    },
    fontSize: {
      options: [10, 12, 14, 'default', 18, 20, 22],
      supportAllValues: true
    },
    heading: {
      options: [
        {
          model: 'paragraph',
          title: 'Paragraph',
          class: 'ck-heading_paragraph'
        },
        {
          model: 'heading1',
          view: 'h1',
          title: 'Heading 1',
          class: 'ck-heading_heading1'
        },
        {
          model: 'heading2',
          view: 'h2',
          title: 'Heading 2',
          class: 'ck-heading_heading2'
        },
        {
          model: 'heading3',
          view: 'h3',
          title: 'Heading 3',
          class: 'ck-heading_heading3'
        },
        {
          model: 'heading4',
          view: 'h4',
          title: 'Heading 4',
          class: 'ck-heading_heading4'
        },
        {
          model: 'heading5',
          view: 'h5',
          title: 'Heading 5',
          class: 'ck-heading_heading5'
        },
        {
          model: 'heading6',
          view: 'h6',
          title: 'Heading 6',
          class: 'ck-heading_heading6'
        }
      ]
    },
    image: {
      toolbar: [
        'toggleImageCaption',
        'imageTextAlternative',
        '|',
        'imageStyle:inline',
        'imageStyle:wrapText',
        'imageStyle:breakText',
        '|',
        'resizeImage'
      ],
      upload: {
        types: ['png', 'jpeg', 'jpg']
      },
    },
    simpleUpload: {
      uploadUrl: '/feedback/upload-image',
      withCredentials: true,
      headers: {
        'X-CSRF-TOKEN': page.props.csrfToken,
      }
    },
    link: {
      addTargetToExternalLinks: true,
      defaultProtocol: 'https://',
      decorators: {
        toggleDownloadable: {
          mode: 'manual',
          label: 'Downloadable',
          attributes: {
            download: 'file'
          }
        }
      }
    },
    list: {
      properties: {
        styles: true,
        startIndex: true,
        reversed: true
      }
    },
    mention: {
      feeds: [
        {
          marker: '@',
          feed: [
            /* See: https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html */
          ]
        }
      ]
    },
    menuBar: {
      isVisible: true
    },
    minimap: {
      container: editorMinimapElement.value,
      extraClasses: 'editor-container_include-minimap ck-minimap__iframe-content'
    },
    title: {
      placeholder: 'Type your title here!'
    },
    placeholder: 'Type or paste your content here!',
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
    }
  }

  if (!props.enableTitle) {
    config.value.plugins = config.value.plugins.filter((plugin: any) => plugin !== Title)
    config.value.title.placeholder = ''
  }

  isLayoutReady.value = true
})

const onReady = (editorInstance: any) => {
  if (editorToolbarElement.value) {
    editorToolbarElement.value.appendChild(editorInstance.ui.view.toolbar.element)
  }
  emit('editor-ready', editorInstance)
}

const onEditorInput = (event: any) => {
  emit('update:modelValue', event)
}
</script>
