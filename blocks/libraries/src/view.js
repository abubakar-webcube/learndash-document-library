import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";

const DEFAULT_LIMIT = 9;
const DEFAULT_LAYOUT = "list";

const parseProps = (node) => {
  const raw = node?.getAttribute("data-props");
  if (!raw) {
    return {};
  }

  try {
    return JSON.parse(raw);
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error("LDL: Unable to parse block props", error);
    return {};
  }
};

const mountApp = (node) => {
  if (!node || node.dataset.ldlMounted) {
    return;
  }

  const props = parseProps(node);
  const initialView = props.layout || DEFAULT_LAYOUT;
  const initialPerPage = Number.isFinite(props.limit) ? props.limit : DEFAULT_LIMIT;
  const enableSearch = props.search !== false;

  const reactRoot = createRoot(node);
  reactRoot.render(
    <StrictMode>
      <App
        documents={props.documents}
        folders={props.folders}
        initialView={initialView}
        initialPerPage={initialPerPage}
        enableSearch={enableSearch}
      />
    </StrictMode>
  );

  node.dataset.ldlMounted = "true";
};

const init = () => {
  const nodes = document.querySelectorAll("[data-ldl-root]");
  nodes.forEach(mountApp);
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
