require 'spec_helper'

describe Medias::Image do
  
  it "should be valid" do
    image = Factory(:image)
    image.should be_valid
  end
  
  it "should not be valid with a zip file" do
    image = Factory.build(:image, :attachment => File.open(fixture_path + '/widget.zip'))
    image.should_not be_valid
  end
  
  describe 'with valid image file' do
     subject { Factory(:image) }
     
     its(:uuid)                           { should be_present }
     its(:attachment_content_type)        { should be_present }
     its(:attachment_content_type)        { should be_kind_of(String) }
     its(:attachment_file_name)           { should be_present }
     its(:attachment_file_name)           { should be_kind_of(String) }
     its(:attachment_file_size)           { should be_present }
     its(:attachment_file_size)           { should be_kind_of(Integer) }
     its(:properties)                     { should be_kind_of(Hash) }

     it { subject.properties[:default_url].should be_present }
     it { subject.properties[:thumb_url].should be_present }
     it { subject.properties[:url].should be_present }
     
   end
   
   it 'should download_image_provided_by_remote_attachment_url from internet' do
     image = Factory(:image, :attachment => nil, :remote_attachment_url => 'http://www.google.ch/images/chrome_48.gif')
     image.attachment.should_not == nil
   end
   
   it 'should download image with extension broken from internet' do
     #if the test is broken, verify that the image is still available on internet
    image = Factory(:image, :attachment => nil, :remote_attachment_url => 'http://dragonartz.files.wordpress.com/2009/05/vector-kids-background-preview-by-dragonart.png%3Fw%3D495%26h%3D495')
    image.attachment_file_name.should == 'vector-kids-background-preview-by-dragonart.png'
  end
  
  it 'should image with 404 error should not be valid' do
    image = Factory.build(:image, :attachment => nil, :remote_attachment_url => 'http://urlthatisnotworking.com/imagenothere.png')
    image.valid?.should == false
  end
    
  it 'properties should not be nil' do
    image = Factory(:image, :properties => nil)
    image.properties.should_not == nil
  end
  
  it 'should transliterate the filename' do
    image = Medias::Image.new
    file = File.new(File.join(RAILS_ROOT, 'spec', 'fixtures', %Q{IT’s, UPPERCASE+super .JPG}), 'rb')
    image.attachment = file
    file.close
    'its-uppercase-super.jpg'.should == image.attachment.original_filename
  end
end


# == Schema Information
#
# Table name: medias
#
#  uuid                    :string(36)      default(""), not null, primary key
#  type                    :string(255)
#  created_at              :datetime
#  updated_at              :datetime
#  properties              :text(16777215)
#  user_id                 :string(36)
#  attachment_file_name    :string(255)
#  system_name             :string(255)
#  title                   :string(255)
#  description             :text
#  attachment_content_type :string(255)
#  attachment_file_size    :integer(4)
#  attachment_updated_at   :datetime
#  favorites               :boolean(1)      default(FALSE)
#  deleted_at              :datetime
#

