import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import {
  InspectorControls,
  useBlockProps
} from "@wordpress/block-editor";
import {
  BaseControl,
  FormTokenField,
  Notice,
  PanelBody,
  RangeControl,
  SelectControl,
  ToggleControl
} from "@wordpress/components";
import metadata from "./block.json";
import App from "./App";
import documents from "./data/documents.json";
import "./editor.css";

const LAYOUT_OPTIONS = [
  { label: __("List", "learndash-document-library"), value: "list" },
  { label: __("Grid", "learndash-document-library"), value: "grid" },
  { label: __("Folder", "learndash-document-library"), value: "folder" }
];

const DEFAULT_BLOCK_DATA = {
  isCategoriesFilterEnabled: true,
  defaultLayout: "list",
  libraries: [],
  categories: []
};

const getBlockData = () => {
  if (typeof window !== "undefined" && window.ldlBlockData) {
    return window.ldlBlockData;
  }
  return DEFAULT_BLOCK_DATA;
};

const labelsFromIds = (options, ids) =>
  options.filter((option) => ids.includes(option.value)).map((option) => option.label);

const idsFromLabels = (options, labels) => {
  const selected = labels
    .map((label) => options.find((option) => option.label === label))
    .filter(Boolean)
    .map((option) => option.value);

  return Array.from(new Set(selected));
};

const MultiSelectControl = ({ label, help, options, value, onChange }) => (
  <BaseControl label={label} help={help}>
    <FormTokenField
      value={labelsFromIds(options, value)}
      suggestions={options.map((option) => option.label)}
      onChange={(labels) => onChange(idsFromLabels(options, labels))}
    />
  </BaseControl>
);

const NumericTokenField = ({ label, help, value, onChange }) => (
  <BaseControl label={label} help={help}>
    <FormTokenField
      value={value.map((val) => String(val))}
      suggestions={[]}
      onChange={(tokens) =>
        onChange(
          tokens
            .map((token) => parseInt(token, 10))
            .filter((token) => !Number.isNaN(token))
        )
      }
    />
  </BaseControl>
);

const Edit = ({ attributes, setAttributes }) => {
  const {
    exclude = [],
    limit = metadata.attributes.limit.default,
    libraries = [],
    categories = [],
    layout = metadata.attributes.layout.default,
    search = metadata.attributes.search.default
  } = attributes;

  const blockData = getBlockData();
  const libraryOptions = Array.isArray(blockData.libraries) ? blockData.libraries : [];
  const categoryOptions = Array.isArray(blockData.categories) ? blockData.categories : [];
  const categoriesDisabled = !blockData.isCategoriesFilterEnabled;
  const apiRoot = typeof window !== "undefined" && window.wpApiSettings?.root ? window.wpApiSettings.root.replace(/\/$/, "") : "";
  const restUrl = blockData.restUrl || (apiRoot ? `${apiRoot}/ldl/v1` : "");
  const restNonce = blockData.restNonce || (typeof window !== "undefined" ? window.wpApiSettings?.nonce || "" : "");

  const blockProps = useBlockProps({ className: "ldl-block-preview" });

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title={__("Display", "learndash-document-library")} initialOpen>
          <SelectControl
            label={__("Layout", "learndash-document-library")}
            value={layout}
            options={LAYOUT_OPTIONS}
            onChange={(value) => setAttributes({ layout: value })}
          />
          <RangeControl
            label={__("Items per page", "learndash-document-library")}
            min={1}
            max={50}
            value={limit}
            onChange={(nextValue) => setAttributes({ limit: nextValue })}
            help={__("Controls how many documents are shown before pagination.", "learndash-document-library")}
          />
          <ToggleControl
            label={__("Enable search", "learndash-document-library")}
            checked={search}
            onChange={(value) => setAttributes({ search: value })}
          />
        </PanelBody>

        <PanelBody title={__("Libraries", "learndash-document-library")} initialOpen={false}>
          <MultiSelectControl
            label={__("Limit to libraries", "learndash-document-library")}
            help={__("Leave empty to include all libraries.", "learndash-document-library")}
            options={libraryOptions}
            value={libraries}
            onChange={(value) => setAttributes({ libraries: value })}
          />
        </PanelBody>

        <PanelBody title={__("Categories", "learndash-document-library")} initialOpen={false}>
          {categoriesDisabled ? (
            <Notice status="info" isDismissible={false}>
              {__("Category filtering is disabled in the plugin settings.", "learndash-document-library")}
            </Notice>
          ) : (
            <MultiSelectControl
              label={__("Limit to categories", "learndash-document-library")}
              help={__("Leave empty to include all categories.", "learndash-document-library")}
              options={categoryOptions}
              value={categories}
              onChange={(value) => setAttributes({ categories: value })}
            />
          )}
        </PanelBody>

        <PanelBody title={__("Exclude IDs", "learndash-document-library")} initialOpen={false}>
          <NumericTokenField
            label={__("Document IDs to exclude", "learndash-document-library")}
            help={__("Provide document IDs separated by commas.", "learndash-document-library")}
            value={exclude}
            onChange={(value) => setAttributes({ exclude: value })}
          />
        </PanelBody>
      </InspectorControls>

      <App
        key={`preview-${layout}-${limit}-${search}`}
        initialView={layout}
        initialPerPage={limit}
        enableSearch={search}
        libraries={libraries}
        categories={categories}
        exclude={exclude}
        visibleColumns={blockData.visible_list_columns || []}
        restUrl={restUrl}
        restNonce={restNonce}
      />
    </div>
  );
};

registerBlockType(metadata.name, {
  edit: Edit,
  save() {
    return null;
  }
});
