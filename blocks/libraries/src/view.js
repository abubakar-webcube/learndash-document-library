import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";

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
  const initialView = props.layout || "list";
  const initialPerPage = Number.isFinite(props.limit) ? props.limit : 9;
  const enableSearch = props.search !== false;
  const visibleColumns = Array.isArray(props.visibleColumns) ? props.visibleColumns : [];

  const reactRoot = createRoot(node);
  reactRoot.render(
    <StrictMode>
      <App
        initialView={initialView}
        initialPerPage={initialPerPage}
        enableSearch={enableSearch}
        currentUserId={props.currentUserId || null}
        libraries={props.libraries || []}
        categories={props.categories || []}
        exclude={props.exclude || []}
        initialFolders={props.folders || []}
        restUrl={props.restUrl || ""}
        restNonce={props.restNonce || ""}
        visibleColumns={visibleColumns}
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
