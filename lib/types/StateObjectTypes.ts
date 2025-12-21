
export type StateObject = {
   url: string,
   title: string,
   targetID: string,
   content: string,
   root?: boolean,
   exact?: boolean,
   reloadTime?: number,
   defaultContent?: string,
}

export type ComponentObject = {
   content: string,
   stateData: Record<string, StateValueType>,
   title?: string,
   targetID?: any,
   reloadTime?: number,
   exact?: boolean,
}

// --- This is the json data type coming from the server
export type TargetInformation = {
   targetIDs: string[],
   currentRoutes: string[],
   defaultContent: string[],
   stateData: Record<string, StateValueType>,
   reloadTime?: number,
   exact: boolean[],
}

export type StateValueType = string | number | boolean | null | { [key: string]: StateValueType } | StateValueType[]
