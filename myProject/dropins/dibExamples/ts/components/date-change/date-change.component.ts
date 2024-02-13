import { AfterViewInit, Component, ViewChild, OnDestroy } from '@angular/core';
import { DibItemComponent, MessagingService, TranslationService, ContainerService, BaseComponent } from 'shared';
import { HttpClient } from '@angular/common/http';
import { take } from 'rxjs/operators';
import { format, parse } from 'date-fns';
@Component({
  selector: 'date-change',
  /* 
    NOTE: this whole file is an Eleutheria template
    The Eleutheria inclfile function is used below to fetch and also minify (>html) the HTML stored in the date-change.component.html file
    Alternatively, store the raw HTML below... 
  */

  templateUrl: `./date-change.component.html`
})

export class DateChangeComponent extends DibItemComponent implements AfterViewInit {
  
    componentName: string = 'date-change';
    private prevValue : string;
    public setDisabled: boolean;
  
    componentContainer : BaseComponent;
    
    // define default style for [ngStyle] directive
    styleObject: {[key: string]: any} =  {'display': 'flex', 'padding': '0px 10px', 'margin': '-5px 0px -10px', 'flex-direction': 'row', 'flex': '1 1 auto'};

    constructor(
        public httpClient : HttpClient, 
        public containerService: ContainerService
    ) {
        super();
    }

    css(key: string, value: any) { 
        this.styleObject[key] = value;
    }

    public onDateChange(event): void {
      let newDate = null;

      if(!!event.value){
          newDate = format(event.value, 'yyyy-MM-dd');
      }

      if (this.container && this.container.clientData && (!newDate || !this.prevValue || newDate != this.prevValue)) {
          
          // See /dropins/dibExamples/GeneralController.php -> startDateChange function
          this.httpClient.post(`/dropins/dibExamples/General/startDateChange/${this.container.view.container.name}`, {
              clientData : this.container.clientData,
              recordData : {
                  "id" : this.data['client_id'], 
                  "column" : this.itemName,
                  "old_date" : this.prevValue,
                  "new_date" : newDate
              }
          
          }).pipe(
              take(1)
          ).subscribe((result : { [key: string]: any; } ) => {
              if (result.success == true) {
                  this.prevValue = newDate;
              }
          });
      }
    }
 
    ngAfterViewInit(): void {

        setTimeout(() => {

            // Get background color from SQL data
            let colorName = this.itemName + '_color';

            // NOTE: this.data will be undefined unless the following is added to attributes: [dibData]="row"

            if (!!this.data[colorName]){
                this.css('background', this.data[colorName]);
                this.css('color', 'white');
            } else {
                this.css('background', 'transparent');
                this.css('color', 'black');
            }

            if (!!this.data && !!this.data[this.itemName]){
                const dateFormat = 'yyyy-MM-dd';
                this.data[this.itemName] = parse(this.data[this.itemName], dateFormat, new Date());
                this.prevValue =  format(this.data[this.itemName], 'yyyy-MM-dd');
            } else {
                this.data[this.itemName] = null;
                this.prevValue = null;
            }

            this.setDisabled = this.container.view.container.name == 'dibexCustomComponentInGrid' && parseInt(this.data['vip']) == 1;
 
        },100);
    }
}

import { NgModule } from '@angular/core';
import { SharedModule } from 'shared';
import { CommonModule } from '@angular/common';

@NgModule({
    imports: [CommonModule, SharedModule],
    declarations: [DateChangeComponent],
    entryComponents: [DateChangeComponent]
})

export default class DateChangeModule {
   static entry = DateChangeComponent;
}