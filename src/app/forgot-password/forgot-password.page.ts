import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { AppComponent } from '../app.component';

@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.page.html',
  styleUrls: ['./forgot-password.page.scss'],
})
export class ForgotPasswordPage implements OnInit {

  creds: FormGroup;
    private email: any;
    private forgetPassRes: any;
    appVersion: any;

  constructor(
    private formBuilder: FormBuilder,
    private mainApp : AppComponent,
  ) {
    this.creds = this.formBuilder.group({
      id: ['', Validators.required]
    });
    this.appVersion = this.mainApp.versionNum;
  }

  ngOnInit() {
  }

}
