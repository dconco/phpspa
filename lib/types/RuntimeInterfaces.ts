
import type { StateObject } from "./StateObjectTypes"

export type EffectType = {
   callback: () => void | (() => void),
   dependencies: Array<unknown>|null,
   cleanup: (() => void)|null,
   lastDeps: unknown[]|null
}

export interface EventPayload {
   route: string,
   success?: boolean,
   error?: any,
   data?: string
}

export interface PopStatePayload {
   route: string,
   state: StateObject | null,
   nativeEvent: PopStateEvent,
   defaultPrevented: boolean,
   preventDefault: () => void,
}

export interface EventPayloadMap {
   beforeload: EventPayload,
   load: EventPayload,
   popstate: PopStatePayload,
}

export interface RuntimeConfig {
   preserveUpdatedHtmlState: boolean,
   waitForStyles: boolean
}

export type EventObject = {
   [K in keyof EventPayloadMap]: Array<(payload: EventPayloadMap[K]) => void>
}

export type CurrentRoutesObject = Record<string, {
   route: URL,
   exact: boolean,
   defaultContent: string
}>
