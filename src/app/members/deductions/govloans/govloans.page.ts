import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { GovloansProvider } from 'src/app/services/govloans/govloans';

@Component({
  selector: 'app-govloans',
  templateUrl: './govloans.page.html',
  styleUrls: ['./govloans.page.scss'],
})
export class GovloansPage implements OnInit {
  fakeUsers: Array<any>;
  fetchingAll: any;
  govLoans: any;

  constructor(
    private mainApp : AppComponent,
    private dedProv: GovloansProvider,
  ) { 
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {
    this.getGovLoans();
  }

  getGovLoans() {
    this.dedProv.getGovLoans().then( res => {
        this.fetchingAll = false;
        let rslt: any = res;
        this.govLoans = rslt.gov_loan;

    }).catch(error => {
        this.fetchingAll = false;
        console.log(error);
    });
  }

}
