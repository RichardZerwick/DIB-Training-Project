/**
 * Redirect the browser to /logout
 * Example: shared.action.logout
 */

import { CommonModule } from '@angular/common';
import { Injectable, Injector, NgModule } from '@angular/core';
import { ActionData, BaseAction, DibAction, BaseComponent } from 'shared';

@DibAction('warehouse.action.logout')
@Injectable({ 
    providedIn: 'root'
})
export class LogoutAction extends BaseAction {
    constructor(public injector: Injector) {
        super();
    }

    async handle(data: ActionData, container: BaseComponent) {

        console.warn("Logging user out");
        debugger;

        
        (window as any).removeEventListener("beforeunload",(window as any).beforeUnloadAction );
        (window as any).location.href="/logout";
    }
}

@NgModule({
    declarations: [],
    imports: [CommonModule],
    exports: [],
    providers: [],
})
export default class LogoutActionModule {
    static service = LogoutAction;
}
