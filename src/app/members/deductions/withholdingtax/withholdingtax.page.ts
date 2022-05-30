import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { WithholdingProvider } from 'src/app/services/withholding/withholding';

@Component({
  selector: 'app-withholdingtax',
  templateUrl: './withholdingtax.page.html',
  styleUrls: ['./withholdingtax.page.scss'],
})
export class WithholdingtaxPage implements OnInit {
  wtax : any;
  withTax: any;
  pageWith: any = '1';
  totalWith: any;
  withTaxF: any;
  pageWithF: any = '1';
  totalWithF: any;
  fakeUsers: Array<any>;
  fetchingAll: any;

  constructor(
    private mainApp : AppComponent,
    private withProv: WithholdingProvider,
  ) { 
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
    this.wtax = 'normal';
  }

  ngOnInit() {
    this.getWithTax();
    this.getWithTaxFixed();
  }

  getWithTax() {
    this.withProv.getWithholdingTax(this.pageWith+'').then( res => {
        let rslt: any = res;
        if (rslt.result) {
          this.withTax = rslt.list;
          this.totalWith = parseInt(rslt.total, 10);
          this.pageWith = parseInt(rslt.page, 10);
        }
    }).catch(error => {
        console.log(error);
    });
  }

  getWithTaxFixed() {
      this.withProv.getWithholdingTaxFixed(this.pageWith+'').then( res => {
          let rslt: any = res;
          if (rslt.result) {
            this.withTaxF = rslt.list;
            this.totalWithF = parseInt(rslt.total, 10);
            this.pageWithF = parseInt(rslt.page, 10);
          }
      }).catch(error => {
          console.log(error);
      });
  }

}
