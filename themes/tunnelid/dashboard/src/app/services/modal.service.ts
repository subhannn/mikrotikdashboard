import { Injectable, NgModule, OnInit, Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA} from '@angular/material';
import { MatDialogModule } from '@angular/material';

export interface DialogData {
  title: string;
  message: string;
}

@Injectable()
export class ModalService {

  constructor(
  	private dialog: MatDialog
  ) { }

  confirmation(message: string){
  	const dialogRef = this.dialog.open(ConfirmationModalComponent, {
  		width: '350px',
  		disableClose: true,
  		data: { message: message }
  	})

  	return dialogRef.afterClosed()
  }

  alert(message: string, title: string){
    const dialogRef = this.dialog.open(AlertModalComponent, {
      width: '450px',
      disableClose: true,
      data: { message: message, title: title }
    })

    return dialogRef.afterClosed()
  }
}

@Component({
  selector: 'app-modal-confirmation',
  templateUrl: './modal/confirm.modal.component.html'
})
export class ConfirmationModalComponent{
  constructor(
  	public dialogRef: MatDialogRef<ConfirmationModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) { }

  onCancel(){
  	this.dialogRef.close()
  }
}

@Component({
  selector: 'app-modal-alert',
  templateUrl: './modal/alert.modal.component.html'
})
export class AlertModalComponent{
  constructor(
    public dialogRef: MatDialogRef<ConfirmationModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) { }

  onOk(){
    this.dialogRef.close()
  }
}

@NgModule({
  declarations: [
    ConfirmationModalComponent, AlertModalComponent,
  ],
  imports: [
    MatDialogModule, CommonModule
  ],
  providers: [],
  entryComponents: [ConfirmationModalComponent, AlertModalComponent]
})
export class ModalModule { }